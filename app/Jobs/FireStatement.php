<?php

namespace App\Jobs;

use App\Models\ApiError;
use Carbon\Carbon as CarbonTime;
use Faker\Generator;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FireStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    private $id;

    private $faker;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws ConnectionException
     */
    public function handle()
    {
        $this->faker = Container::getInstance()->make(Generator::class);
        $url = config('app.remote_url');

        $date_sent = $this->faker->dateTimeThisYear->format('Y-m-d');
        $content_date = $this->faker->dateTimeThisYear->format('Y-m-d');
        $application_date = $this->faker->dateTimeThisYear->format('Y-m-d');

        $data = [
            'start_date' => $date_sent,
            'content_date' => $content_date,
            'application_date' => $application_date,
            'countries_list' => $this->faker->randomElements(['IE', 'DE', 'FR', 'NL', 'BE']),
            'decision_visibility' => fake()->randomElements(['DECISION_VISIBILITY_CONTENT_REMOVED', 'DECISION_VISIBILITY_CONTENT_DISABLED', 'DECISION_VISIBILITY_CONTENT_DEMOTED']),
            'decision_monetary' => fake()->randomElement(['DECISION_MONETARY_SUSPENSION', 'DECISION_MONETARY_TERMINATION']),
            'decision_provision' => fake()->randomElement(['DECISION_PROVISION_PARTIAL_SUSPENSION', 'DECISION_PROVISION_TOTAL_SUSPENSION', 'DECISION_PROVISION_PARTIAL_TERMINATION', 'DECISION_PROVISION_TOTAL_TERMINATION']),
            'decision_account' => fake()->randomElement(['DECISION_ACCOUNT_SUSPENDED', 'DECISION_ACCOUNT_TERMINATED']),
            'content_type' => fake()->randomElements(['CONTENT_TYPE_TEXT', 'CONTENT_TYPE_VIDEO', 'CONTENT_TYPE_IMAGE']),
            'category' => fake()->randomElement(['STATEMENT_CATEGORY_ANIMAL_WELFARE', 'STATEMENT_CATEGORY_DATA_PROTECTION_AND_PRIVACY_VIOLATIONS', 'STATEMENT_CATEGORY_ILLEGAL_OR_HARMFUL_SPEECH', 'STATEMENT_CATEGORY_INTELLECTUAL_PROPERTY_INFRINGEMENTS']),

            'incompatible_content_illegal' => fake()->randomElement(['Yes', 'No']),
            'decision_facts' => 'facts about the decision',
            'automated_detection' => fake()->randomElement(['Yes', 'No']),
            'automated_decision' => fake()->randomElement(['AUTOMATED_DECISION_FULLY', 'AUTOMATED_DECISION_PARTIALLY', 'AUTOMATED_DECISION_NOT_AUTOMATED']),
            'source_type' => fake()->randomElement(['SOURCE_ARTICLE_16', 'SOURCE_TRUSTED_FLAGGER', 'SOURCE_VOLUNTARY']),
            'source' => fake()->word,
            'puid' => fake()->uuid,
            'url' => fake()->url,
        ];

        $data['decision_ground'] = fake()->randomElement(['DECISION_GROUND_ILLEGAL_CONTENT', 'DECISION_GROUND_INCOMPATIBLE_CONTENT']);
        if ($data['decision_ground'] == 'DECISION_GROUND_ILLEGAL_CONTENT') {
            $data['illegal_content_legal_ground'] = fake()->text;
            $data['illegal_content_explanation'] = fake()->text;
        }
        if ($data['decision_ground'] == 'DECISION_GROUND_INCOMPATIBLE_CONTENT') {
            $data['incompatible_content_ground'] = fake()->text;
            $data['incompatible_content_explanation'] = fake()->text;
        }

        // Create an array to store the cloned objects
        $statements = [];

        // Clone the object 100 times and change the 'field1' value in each clone
        for ($i = 0; $i < 100; $i++) {
            $clonedStatement = $data; // Clone the object
            $clonedStatement['puid'] = fake()->uuid; // Modify the 'field1' in the cloned object
            $statements[] = $clonedStatement; // Store the cloned object in the array
        }

        $data = ['statements' => $statements];

        $startTime = CarbonTime::now();

        // Record the first statement timestamp if not already set
        if (Cache::get('batch_processing_start') === null) {
            Cache::put('batch_processing_start', $startTime->toIso8601String(), now()->addHours(1));
            Log::info('[METRICS] First batch statement started sending', [
                'timestamp' => $startTime->toIso8601String(),
                'batch_id' => $this->id,
            ]);
        }

        // Fire-and-forget: send data with short timeout, don't wait for response
        // The server receives data but doesn't respond quickly, so we just ensure data is sent
        try {
            Http::timeout(10)->connectTimeout(10)->withHeaders([
                'Authorization' => 'Bearer '.config('app.remote_token'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $data);

            Log::info('[BATCH] Batch sent successfully', ['batch_id' => $this->id]);
        } catch (ConnectionException $e) {
            // Timeout is expected - data was sent but server doesn't respond quickly
            // Check if it's a timeout (data was likely sent) vs connection failure (data not sent)
            $isTimeout = str_contains(strtolower($e->getMessage()), 'timeout');

            if ($isTimeout) {
                Log::info('[BATCH] Batch sent (timeout waiting for response, data likely received)', [
                    'batch_id' => $this->id,
                ]);
            } else {
                // Actual connection failure - data may not have been sent
                Log::error('[ERROR] Batch statement API connection failed', [
                    'batch_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                ApiError::create([
                    'statement_response_id' => null,
                    'url' => $url,
                    'method' => 'POST',
                    'request_payload' => json_encode($data),
                    'status_code' => null,
                    'error_message' => $e->getMessage(),
                    'response_body' => null,
                ]);
            }
        }

        $endTime = CarbonTime::now();

        // Always update the end time as this could be the last statement
        Cache::put('batch_processing_end', $endTime->toIso8601String(), now()->addHours(1));

        // Increment the processed count
        $processed = Cache::increment('batch_processed_count');
        $total = Cache::get('total_batch_count');

        // Always log the current progress for debugging
        Log::debug('[METRICS] Batch progress', [
            'processed' => $processed,
            'total' => $total,
            'batch_id' => $this->id,
        ]);

        // If this is the last batch, log the complete metrics
        if ($processed >= $total) {
            $startTimeStr = Cache::get('batch_processing_start');
            $endTimeStr = Cache::get('batch_processing_end');

            if ($startTimeStr && $endTimeStr) {
                $startTimeObj = CarbonTime::parse($startTimeStr);
                $endTimeObj = CarbonTime::parse($endTimeStr);
                $duration = $endTimeObj->diffInSeconds($startTimeObj);

                Log::info('[METRICS] All batch statements completed', [
                    'timestamp_start' => $startTimeStr,
                    'timestamp_end' => $endTimeStr,
                    'duration_seconds' => $duration,
                    'total_batches' => $total,
                    'processed_batches' => $processed,
                    'total_statements' => $total * 100,
                ]);
            }
        }

    }
}

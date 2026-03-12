<?php

namespace Tests\Feature\Broadcasting;

use App\Domain\Backoffice\Models\BackofficeAgent;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class AuthorizeBackofficeOrdersChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
        config()->set('broadcasting.default', 'test-reverb');
        config()->set('broadcasting.connections.test-reverb', [
            'driver' => 'test-reverb',
            'key' => 'test-key',
            'secret' => 'test-secret',
        ]);

        Broadcast::extend('test-reverb', static function ($app, array $config): DeterministicPrivateChannelBroadcaster {
            return new DeterministicPrivateChannelBroadcaster(
                (string) $config['key'],
                (string) $config['secret'],
            );
        });

        app(BroadcastManager::class)->forgetDrivers();
        require base_path('routes/channels.php');
    }

    public function test_allows_an_agent_with_recent_orders_permission_to_authorize_the_backoffice_orders_channel(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => true,
        ]);

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-backoffice.orders',
                'socket_id' => '1234.5678',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'auth',
            ]);
    }

    public function test_forbids_an_agent_without_recent_orders_permission_from_authorizing_the_backoffice_orders_channel(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => false,
        ]);

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-backoffice.orders',
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    }

    public function test_agent_id_one_bypasses_the_backoffice_orders_channel_permission_check(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::ID => 1,
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => false,
        ]);

        $this->assertSame(1, (int) $agent->getKey());

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-backoffice.orders',
                'socket_id' => '1234.5678',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'auth',
            ]);
    }

    private function issueBackofficeAccessToken(BackofficeAgent $agent): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('backoffice');

        return $guard->login($agent);
    }
}

final class DeterministicPrivateChannelBroadcaster extends Broadcaster
{
    public function __construct(
        private readonly string $key,
        private readonly string $secret,
    ) {
    }

    public function auth($request): array
    {
        $channelName = $this->normalizeChannelName((string) $request->input('channel_name'));

        if ($channelName === '' || ! $this->retrieveUser($request, $channelName)) {
            throw new AccessDeniedHttpException();
        }

        return $this->verifyUserCanAccessChannel($request, $channelName);
    }

    public function validAuthenticationResponse($request, $result): array
    {
        $stringToSign = sprintf(
            '%s:%s',
            (string) $request->input('socket_id'),
            (string) $request->input('channel_name'),
        );

        return [
            'auth' => sprintf(
                '%s:%s',
                $this->key,
                hash_hmac('sha256', $stringToSign, $this->secret),
            ),
        ];
    }

    public function broadcast(array $channels, $event, array $payload = []): void
    {
        // The Task 5 API coverage only needs deterministic channel auth responses.
    }

    private function normalizeChannelName(string $channelName): string
    {
        return match (true) {
            str_starts_with($channelName, 'private-') => substr($channelName, 8),
            str_starts_with($channelName, 'presence-') => substr($channelName, 9),
            default => $channelName,
        };
    }
}

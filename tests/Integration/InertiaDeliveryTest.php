<?php

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Middleware as InertiaMiddleware;

final class StoreInertiaOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}

beforeEach(function () {
    Route::middleware(['web', InertiaMiddleware::class])->group(function (): void {
        Route::get('/pulsar-inertia/orders', function () {
            $order = new class(['name' => 'Ada']) extends JsonResource
            {
                /**
                 * @return array{name: string}
                 */
                public function toArray(Request $request): array
                {
                    return ['name' => $this->resource['name']];
                }
            };

            return Inertia::render('Orders/Index', [
                'actor' => request()->session()->get('actor'),
                'order' => $order,
            ]);
        })->name('pulsar-inertia.orders.index');

        Route::post('/pulsar-inertia/orders', function (StoreInertiaOrderRequest $request) {
            $request->validated();

            return to_route('pulsar-inertia.orders.index');
        })->name('pulsar-inertia.orders.store');

        Route::put('/pulsar-inertia/orders/1', function (StoreInertiaOrderRequest $request) {
            $request->validated();

            return to_route('pulsar-inertia.orders.index');
        })->name('pulsar-inertia.orders.update');
    });
});

it('returns an Inertia page with session data and a Resource prop', function () {
    $response = $this
        ->withSession(['actor' => 'admin'])
        ->withHeader('X-Inertia', 'true')
        ->get('/pulsar-inertia/orders');

    $response
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Orders/Index')
        ->assertJsonPath('props.actor', 'admin')
        ->assertJsonPath('props.order.data.name', 'Ada');
});

it('redirects invalid input back with flashed validation errors', function () {
    $response = $this
        ->from('/pulsar-inertia/orders')
        ->withHeader('X-Inertia', 'true')
        ->post('/pulsar-inertia/orders');

    $response
        ->assertRedirect('/pulsar-inertia/orders')
        ->assertSessionHasErrors('name');

    $this
        ->withHeader('X-Inertia', 'true')
        ->get('/pulsar-inertia/orders')
        ->assertOk()
        ->assertJsonPath('props.errors.name', 'The name field is required.');
});

it('preserves Laravel redirects and upgrades non-POST Inertia redirects to 303', function () {
    $this
        ->withHeader('X-Inertia', 'true')
        ->post('/pulsar-inertia/orders', ['name' => 'Ada'])
        ->assertRedirect('/pulsar-inertia/orders')
        ->assertStatus(302);

    $this
        ->withHeader('X-Inertia', 'true')
        ->put('/pulsar-inertia/orders/1', ['name' => 'Ada'])
        ->assertRedirect('/pulsar-inertia/orders')
        ->assertStatus(303);
});

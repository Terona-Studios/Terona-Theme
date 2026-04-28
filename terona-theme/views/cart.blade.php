<div class="container max-w-7xl mx-auto px-6 lg:px-8 pt-6 pb-12">
    <div class="grid md:grid-cols-4 gap-6">
        <div class="col-span-3 flex flex-col gap-4">
            @if (Cart::items()->count() === 0)
                <div class="bg-background-secondary border border-neutral/20 rounded-xl p-12 text-center shadow-sm overflow-hidden">
                    <div class="flex flex-col items-center space-y-6">
                        <div class="relative">
                            <div class="absolute inset-0 bg-primary/20 rounded-full blur-2xl scale-150"></div>
                            <span class="relative bg-gradient-to-br from-primary/10 to-primary/5 rounded-full p-6 inline-flex">
                                <x-ri-shopping-cart-line class="size-16 text-primary/60" />
                            </span>
                        </div>
                        <div class="space-y-3">
                            <h1 class="text-3xl font-bold">{{ __('product.empty_cart') }}</h1>
                            <p class="text-base/60 max-w-md text-lg">Your cart is empty. Browse our products and add something great!</p>
                        </div>
                        <div class="pt-4">
                            <a href="{{ route('home') }}" wire:navigate>
                                <x-button.primary class="px-8 py-3 flex items-center gap-2">
                                    <x-ri-shopping-bag-line class="size-5" />
                                    {{ __('product.continue_shopping') ?? 'Continue Shopping' }}
                                </x-button.primary>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @foreach (Cart::items() as $item)
                <div class="bg-background-secondary border border-neutral/20 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col gap-5">
                    <div class="flex flex-col gap-3 pb-4 border-b border-neutral/10">
                        <h2 class="text-2xl font-bold">{{ $item->product->name }}</h2>
                        @if (count($item->config_options) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($item->config_options as $option)
                                    <div class="inline-flex items-center gap-2 bg-neutral/5 rounded-lg px-3 py-1.5 text-sm">
                                        <span class="font-medium text-base/70">{{ $option['option_name'] }}:</span>
                                        <span class="text-base/90">{{ $option['value_name'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                        <div>
                            <div class="text-3xl font-bold text-primary">
                                {{ $item->price->format($item->price->total * $item->quantity) }}
                            </div>
                            @if ($item->quantity > 1)
                                <div class="text-sm text-base/60 mt-1">
                                    {{ $item->price }} × {{ $item->quantity }} items
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            @if ($item->product->allow_quantity == 'combined')
                                <div class="flex items-center bg-background border border-neutral/20 rounded-lg overflow-hidden">
                                    <x-button.secondary wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                        class="h-full !w-fit px-3">
                                        <x-ri-subtract-line class="size-4" />
                                    </x-button.secondary>
                                    <input type="text" class="h-10 text-center font-semibold w-16 bg-transparent border-0" disabled value="{{ $item->quantity }}" />
                                    <x-button.secondary wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                        class="h-full !w-fit px-3">
                                        <x-ri-add-line class="size-4" />
                                    </x-button.secondary>
                                </div>
                            @endif

                            @php
                                $optionsQuery = collect($item->config_options ?? [])
                                    ->filter(fn ($option) => isset($option['value']) && $option['value'] !== '' && $option['value'] !== null)
                                    ->mapWithKeys(fn ($option) => [$option['option_id'] => $option['value']])
                                    ->all();

                                $configQuery = collect($item->checkout_config ?? [])
                                    ->filter(fn ($value) => $value !== '' && $value !== null)
                                    ->all();

                                $routeParams = [
                                    'category' => $item->product->category,
                                    'product' => $item->product,
                                    'edit' => $item->id,
                                    'plan' => $item->plan->id,
                                ];

                                if (!empty($optionsQuery)) {
                                    $routeParams['options'] = $optionsQuery;
                                }

                                if (!empty($configQuery)) {
                                    $routeParams['config'] = $configQuery;
                                }
                            @endphp

                            <a href="{{ route('products.show', [$item->product->category, $item->product]) }}" wire:navigate>
                                <x-button.primary class="h-fit whitespace-nowrap flex items-center gap-2">
                                    <x-ri-edit-line class="size-4" />
                                    {{ __('product.edit') }}
                                </x-button.primary>
                            </a>

                            <x-button.danger wire:click="removeProduct({{ $item->id }})" class="h-fit !w-fit whitespace-nowrap flex items-center gap-2">
                                <x-loading target="removeProduct({{ $item->id }})" />
                                <div wire:loading.remove wire:target="removeProduct({{ $item->id }})">
                                    <x-ri-delete-bin-line class="size-4" />
                                    {{ __('product.remove') }}
                                </div>
                            </x-button.danger>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-4">
            @if (Cart::items()->count() > 0)
                <div class="bg-background-secondary border border-neutral/20 rounded-xl p-6 shadow-sm sticky top-24 space-y-6">
                    <h2 class="text-2xl font-bold border-b border-neutral/10 pb-4">{{ __('product.order_summary') }}</h2>

                    @if (!$coupon)
                        <div class="space-y-2.5">
                            <x-form.input wire:model="coupon" name="coupon" label="Coupon Code" placeholder="Enter coupon..." />
                            <x-button.primary wire:click="applyCoupon" wire:loading.attr="disabled" class="w-full justify-center py-2.5">
                                <x-loading target="applyCoupon" />
                                <div wire:loading.remove wire:target="applyCoupon" class="flex items-center gap-2">
                                    <x-ri-coupon-line class="size-4" />
                                    {{ __('product.apply') }}
                                </div>
                            </x-button.primary>
                        </div>
                    @else
                        <div class="flex items-center justify-between bg-green-500/10 border border-green-500/30 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-ri-check-double-line class="size-5 text-green-500" />
                                <span class="font-semibold text-green-600">{{ $coupon->code }}</span>
                            </div>
                            <x-button.secondary wire:click="removeCoupon" class="h-fit !w-fit text-sm">
                                {{ __('product.remove') }}
                            </x-button.secondary>
                        </div>
                    @endif

                    <div class="space-y-3 py-4 border-y border-neutral/10">
                        <div class="flex justify-between items-center">
                            <span class="text-base/70">{{ __('invoices.subtotal') }}</span>
                            <span class="font-semibold text-lg">{{ $total->format($total->subtotal) }}</span>
                        </div>
                        @if ($total->tax > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-base/70">{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%)</span>
                                <span class="font-semibold text-lg">{{ $total->format($total->tax) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-lg font-bold">{{ __('invoices.total') }}</span>
                            <span class="text-3xl font-bold text-primary">{{ $total->format($total->total) }}</span>
                        </div>

                        @if (config('settings.tos'))
                            <div class="p-4 rounded-lg bg-neutral/5 border border-neutral/10">
                                <x-form.checkbox wire:model="tos" name="tos" class="text-sm">
                                    {{ __('product.tos') }}
                                    <a href="{{ config('settings.tos') }}" target="_blank"
                                        class="text-primary hover:text-primary/80 underline ml-1">
                                        {{ __('product.tos_link') ?? 'terms' }}
                                    </a>
                                </x-form.checkbox>
                            </div>
                        @endif

                        <x-button.primary wire:click="checkout" wire:loading.attr="disabled"
                            class="w-full justify-center py-3 text-lg font-semibold">
                            <x-loading target="checkout" />
                            <div wire:loading.remove wire:target="checkout" class="flex items-center gap-2">
                                <x-ri-arrow-right-line class="size-5" />
                                {{ __('product.checkout') }}
                            </div>
                        </x-button.primary>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

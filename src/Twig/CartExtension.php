<?php

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', [$this, 'getCartCount']),
            new TwigFunction('cart_total', [$this, 'getCartTotal']),
        ];
    }

    public function getCartCount(): int
    {
        return $this->cartService->getCount();
    }

    public function getCartTotal(): float
    {
        return $this->cartService->getTotal();
    }
}

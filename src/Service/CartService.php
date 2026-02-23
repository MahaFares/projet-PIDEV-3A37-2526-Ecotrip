<?php

namespace App\Service;

use App\Entity\Enum\ReservationType;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const SESSION_KEY = 'ecotrip_cart';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    public function getCart(): array
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, [
            'hebergements' => [],
            'activities' => [],
            'transports' => [],
            'produits' => [],
        ]);
    }

    public function setCart(array $cart): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $cart);
    }

    /**
     * @param array{dateFrom?: string, dateTo?: string, guests?: int} $options Y-m-d dates, number of guests
     */
    public function addHebergement(int $id, float $price, string $label, int $nights = 1, array $options = []): void
    {
        $cart = $this->getCart();
        $key = 'h_' . $id;
        $cart['hebergements'][$key] = array_merge([
            'id' => $id,
            'price' => $price,
            'label' => $label,
            'nights' => $nights,
        ], array_filter([
            'dateFrom' => $options['dateFrom'] ?? null,
            'dateTo' => $options['dateTo'] ?? null,
            'guests' => isset($options['guests']) ? (int) $options['guests'] : null,
        ], fn ($v) => $v !== null));
        $this->setCart($cart);
    }

    /**
     * @param array{reservedAt?: string, participants?: int} $options DateTime string, number of participants
     */
    public function addActivity(int $id, float $price, string $label, int $quantity = 1, array $options = []): void
    {
        $cart = $this->getCart();
        $key = 'a_' . $id;
        $cart['activities'][$key] = array_merge([
            'id' => $id,
            'price' => $price,
            'label' => $label,
            'quantity' => $quantity,
        ], array_filter([
            'reservedAt' => $options['reservedAt'] ?? null,
            'participants' => isset($options['participants']) ? (int) $options['participants'] : null,
        ], fn ($v) => $v !== null));
        $this->setCart($cart);
    }

    /**
     * @param array{depart?: string, arrivee?: string, travelDate?: string, passengers?: int} $options
     */
    public function addTransport(int $id, float $price, string $label, int $quantity = 1, array $options = []): void
    {
        $cart = $this->getCart();
        $key = 't_' . $id;
        $cart['transports'][$key] = array_merge([
            'id' => $id,
            'price' => $price,
            'label' => $label,
            'quantity' => $quantity,
        ], array_filter([
            'depart' => $options['depart'] ?? null,
            'arrivee' => $options['arrivee'] ?? null,
            'travelDate' => $options['travelDate'] ?? null,
            'passengers' => isset($options['passengers']) ? (int) $options['passengers'] : null,
        ], fn ($v) => $v !== null));
        $this->setCart($cart);
    }

    public function addProduit(int $id, float $price, string $label, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $key = 'p_' . $id;
        if (isset($cart['produits'][$key])) {
            $cart['produits'][$key]['quantity'] += $quantity;
        } else {
            $cart['produits'][$key] = [
                'id' => $id,
                'price' => $price,
                'label' => $label,
                'quantity' => $quantity,
            ];
        }
        $this->setCart($cart);
    }

    public function remove(string $type, string $key): void
    {
        $cart = $this->getCart();
        if (isset($cart[$type][$key])) {
            unset($cart[$type][$key]);
            $this->setCart($cart);
        }
    }

    public function updateProduitQuantity(string $key, int $quantity): void
    {
        $cart = $this->getCart();
        if (isset($cart['produits'][$key]) && $quantity > 0) {
            $cart['produits'][$key]['quantity'] = $quantity;
            $this->setCart($cart);
        }
    }

    public function getCount(): int
    {
        $cart = $this->getCart();
        $count = 0;
        foreach ($cart['hebergements'] ?? [] as $item) {
            $count++;
        }
        foreach ($cart['activities'] ?? [] as $item) {
            $count++;
        }
        foreach ($cart['transports'] ?? [] as $item) {
            $count++;
        }
        foreach ($cart['produits'] ?? [] as $item) {
            $count += $item['quantity'] ?? 1;
        }
        return $count;
    }

    public function getTotal(): float
    {
        $cart = $this->getCart();
        $total = 0;
        foreach ($cart['hebergements'] ?? [] as $item) {
            $total += ($item['price'] ?? 0) * ($item['nights'] ?? 1);
        }
        foreach ($cart['activities'] ?? [] as $item) {
            $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        foreach ($cart['transports'] ?? [] as $item) {
            $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        foreach ($cart['produits'] ?? [] as $item) {
            $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        return $total;
    }

    public function clear(): void
    {
        $this->setCart([
            'hebergements' => [],
            'activities' => [],
            'transports' => [],
            'produits' => [],
        ]);
    }

    public function isEmpty(): bool
    {
        return $this->getCount() === 0;
    }
}

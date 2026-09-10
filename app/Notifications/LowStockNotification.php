<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Product $product,
        protected int $currentQty,
        protected int $reorderLevel
    ) {}

    /**
     * 'database' -> powers the in-app "Security/Inventory Alerts" bell icon on the
     *   admin dashboard (reads from the notifications table Laravel scaffolds).
     * 'mail' -> optional, uncomment if you want actual emails; keep 'database'
     *   regardless so the dashboard alert always works even without mail configured.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'current_quantity' => $this->currentQty,
            'reorder_level' => $this->reorderLevel,
            'message' => "{$this->product->name} (SKU: {$this->product->sku}) is low on stock: {$this->currentQty} left.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Stock Alert: '.$this->product->name)
            ->line("Product \"{$this->product->name}\" (SKU: {$this->product->sku}) has fallen to {$this->currentQty} units.")
            ->line("Reorder level is set at {$this->reorderLevel}.")
            ->action('View Product', url('/admin/products/'.$this->product->id));
    }
}

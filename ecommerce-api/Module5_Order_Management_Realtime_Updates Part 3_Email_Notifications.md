# Module 5: Order Management and Realtime Updates – Part 3: Email Notifications & Customer Communication

## Introduction

When customers place an order, they expect quick confirmation and updates as their order moves through payment, shipping, and delivery. Sending these emails automatically builds trust and saves your support team from endless “where is my order?” questions.

In this part, we’ll connect our **order status system** to Laravel’s **notification system**. Whenever an order status changes, an email will be queued and sent to the customer. This way, your API stays fast, and customers stay informed.

---

## Step 1: Configure Mail and Queue

In your `.env` file:

```env
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Make sure queue tables exist:

```bash
php artisan queue:table
php artisan migrate
```

---

## Step 2: Create Notifications

We’ll create simple notifications for the main events:

```bash
php artisan make:notification OrderConfirmationNotification
php artisan make:notification OrderShippedNotification
php artisan make:notification OrderDeliveredNotification
php artisan make:notification OrderCancelledNotification
```

### Example: Order Confirmation Notification

```php
<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->order->load(['items.product', 'user']);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Order Confirmation #{$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Thank you for your order! Here are the details:")
            ->line("Order Total: $" . number_format($this->order->total, 2));

        foreach ($this->order->items as $item) {
            $productName = $item->product->name ?? 'Unknown';
            $mail->line("• {$productName} (x{$item->quantity})");
        }

        return $mail->line("We’ll let you know once your order is shipped.")
            ->salutation("— The " . config('app.name') . " Team");
    }
}
```


---

## Step 3: Listen to Status Changes

We already broadcast status changes with `OrderStatusChanged`. Now we’ll also send emails when that event fires.

```bash
php artisan make:listener SendOrderStatusEmail
```

```php
<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Enums\OrderStatus;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\OrderShippedNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderCancelledNotification;

class SendOrderStatusEmail
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        switch ($order->status) {
            case OrderStatus::PAID:
                $order->user->notify(new OrderConfirmationNotification($order));
                break;

            case OrderStatus::SHIPPED:
                $order->user->notify(new OrderShippedNotification($order));
                break;

            case OrderStatus::DELIVERED:
                $order->user->notify(new OrderDeliveredNotification($order));
                break;

            case OrderStatus::CANCELLED:
                $order->user->notify(new OrderCancelledNotification($order));
                break;

            default:
                // No email needed for PENDING/PROCESSING
                break;
        }
    }
}
```

---

## Step 4: Register Listener

In `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\OrderStatusChanged::class => [
        \App\Listeners\SendOrderStatusEmail::class,
    ],
];
```

---

## Step 5: Run the Queue Worker

Emails are queued, so don’t forget to run the worker:

```bash
php artisan queue:work
```

Now when you change an order’s status (via Postman or your admin API), the customer will automatically get an email.

---



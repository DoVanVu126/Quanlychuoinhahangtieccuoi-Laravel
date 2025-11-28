<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Booking;


class BookingCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, $user)
    {
        $this->booking = $booking;
         $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Đặt tiệc thành công')
                    ->view('emails.booking_created')
                    ->with([
                        'bookingId' => $this->booking->booking_id,
                        'eventDate' => $this->booking->event_date,
                        'eventTime' => $this->booking->event_time,
                        'numberOfTables' => $this->booking->number_of_tables,
                        'price' => $this->booking->price,
                    ]);
    }
}

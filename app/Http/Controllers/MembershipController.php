<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\Booking;

class MembershipController extends Controller
{
    // Hiển thị membership của user
    public function show($user_id)
    {
        $membership = Membership::updateMembership($user_id); // tính booking_count và level tự động

        return response()->json([
            'membership' => $membership,
            'discount' => $this->getDiscount($membership->level)
        ]);
    }

    // Ưu đãi theo level
   private function getDiscount($level)
{
    switch($level){
        case 'Silver': return 5;
        case 'Gold': return 10;
        case 'VIP': return 12;
        case 'Diamond': return 15;
        default: return 2; // Đồng / Normal
    }
}
}

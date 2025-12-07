<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Lấy tất cả thống kê cho dashboard
     */
    public function getStatistics(Request $request)
    {
        try {
            $currentMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            
            // 1. Thống kê tổng quan
            $stats = $this->getOverviewStats($currentMonth, $lastMonth);
            
            // 2. Doanh thu theo tháng (12 tháng gần nhất)
            $revenueByMonth = $this->getRevenueByMonth();
            
            // 3. Thống kê theo trạng thái đơn
            $bookingsByStatus = $this->getBookingsByStatus();
            
            // 4. Đơn đặt tiệc gần đây (10 đơn mới nhất)
            $recentBookings = $this->getRecentBookings();
            
            // 5. Top nhà hàng phổ biến
            $topRestaurants = $this->getTopRestaurants();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'revenueByMonth' => $revenueByMonth,
                'bookingsByStatus' => $bookingsByStatus,
                'recentBookings' => $recentBookings,
                'topRestaurants' => $topRestaurants
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tải thống kê: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Thống kê tổng quan
     */
    private function getOverviewStats($currentMonth, $lastMonth)
    {
        // Tổng đơn tháng này
        $totalBookingsThisMonth = Booking::where('created_at', '>=', $currentMonth)->count();
        
        // Tổng đơn tháng trước
        $totalBookingsLastMonth = Booking::whereBetween('created_at', [
            $lastMonth,
            $lastMonth->copy()->endOfMonth()
        ])->count();
        
        // Tính % tăng trưởng đơn đặt
        $bookingGrowth = $totalBookingsLastMonth > 0 
            ? round((($totalBookingsThisMonth - $totalBookingsLastMonth) / $totalBookingsLastMonth) * 100, 1)
            : 0;
        
        // Tổng doanh thu tháng này (chỉ đơn confirmed và completed)
        $totalRevenueThisMonth = Booking::where('created_at', '>=', $currentMonth)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');
        
        // Tổng doanh thu tháng trước
        $totalRevenueLastMonth = Booking::whereBetween('created_at', [
            $lastMonth,
            $lastMonth->copy()->endOfMonth()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');
        
        // Tính % tăng trưởng doanh thu
        $revenueGrowth = $totalRevenueLastMonth > 0
            ? round((($totalRevenueThisMonth - $totalRevenueLastMonth) / $totalRevenueLastMonth) * 100, 1)
            : 0;
        
        // Khách hàng mới tháng này
        $newCustomers = Customer::where('created_at', '>=', $currentMonth)->count();
        
        // Đơn chờ duyệt
        $pendingBookings = Booking::where('status', 'pending')->count();
        
        // Tổng đơn toàn bộ hệ thống
        $totalBookings = Booking::count();
        
        // Tổng doanh thu toàn bộ hệ thống
        $totalRevenue = Booking::whereIn('status', ['confirmed', 'completed'])->sum('price');
        
        return [
            'totalBookings' => $totalBookings,
            'totalRevenue' => (float) $totalRevenue,
            'newCustomers' => $newCustomers,
            'pendingBookings' => $pendingBookings,
            'bookingGrowth' => $bookingGrowth,
            'revenueGrowth' => $revenueGrowth
        ];
    }
    
    /**
     * Doanh thu theo tháng (12 tháng gần nhất)
     */
    private function getRevenueByMonth()
    {
        $months = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $revenue = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('price');
            
            $months[] = [
                'month' => 'Th' . $date->format('m/Y'),
                'revenue' => (float) $revenue
            ];
        }
        
        return $months;
    }
    
    /**
     * Thống kê đơn theo trạng thái
     */
    private function getBookingsByStatus()
    {
        $statuses = Booking::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        
        return [
            'pending' => $statuses['pending'] ?? 0,
            'confirmed' => $statuses['confirmed'] ?? 0,
            'completed' => $statuses['completed'] ?? 0,
            'cancelled' => $statuses['cancelled'] ?? 0
        ];
    }
    
    /**
     * Đơn đặt tiệc gần đây (10 đơn mới nhất)
     */
    private function getRecentBookings()
    {
        $bookings = Booking::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return $bookings->map(function ($booking) {
            // Lấy thông tin customer
            $customerName = 'N/A';
            try {
                $customer = Customer::find($booking->customer_id);
                if ($customer && $customer->user) {
                    $customerName = $customer->user->full_name ?? $customer->user->username ?? 'N/A';
                }
            } catch (\Exception $e) {
                // Bỏ qua lỗi
            }
            
            // Lấy tên sảnh
            $hallName = 'N/A';
            try {
                $hall = DB::table('halls')->where('hall_id', $booking->hall_id)->first();
                $hallName = $hall->name ?? 'N/A';
            } catch (\Exception $e) {
                // Bỏ qua lỗi
            }
            
            return [
                'booking_id' => $booking->booking_id,
                'customer_name' => $customerName,
                'hall_name' => $hallName,
                'event_date' => $booking->event_date,
                'price' => (float) $booking->price,
                'status' => $booking->status
            ];
        });
    }
    
    /**
     * Top 5 nhà hàng phổ biến
     */
    private function getTopRestaurants()
    {
        $topRestaurants = DB::table('restaurants')
            ->select('restaurants.restaurant_id as id', 'restaurants.name', DB::raw('COUNT(bookings.booking_id) as bookings'))
            ->leftJoin('halls', 'restaurants.restaurant_id', '=', 'halls.restaurant_id')
            ->leftJoin('bookings', 'halls.hall_id', '=', 'bookings.hall_id')
            ->groupBy('restaurants.restaurant_id', 'restaurants.name')
            ->orderBy('bookings', 'desc')
            ->limit(5)
            ->get();
        
        return $topRestaurants->map(function ($restaurant) {
            return [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'bookings' => $restaurant->bookings
            ];
        });
    }
}
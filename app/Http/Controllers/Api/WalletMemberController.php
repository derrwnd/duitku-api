<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Models\WalletMember;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WalletMemberController extends Controller
{
    /**
     * Daftar anggota dompet bersama
     */
    public function index(Request $request, Wallet $wallet)
    {
        // Cek apakah user punya akses ke wallet ini
        $isMember = $this->hasAccess(
            $wallet,
            $request->user()->id
        );

        if (!$isMember) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $members = WalletMember::with('user:id,name,email')
            ->where('wallet_id', $wallet->id)
            ->get();

        return response()->json($members);
    }

    /**
     * Tambahkan anggota ke dompet bersama
     */
    public function store(Request $request, Wallet $wallet)
    {
        // Hanya owner yang bisa menambah anggota
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Hanya pemilik dompet yang dapat menambah anggota'
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $targetUser = User::where(
            'email',
            $validated['email']
        )->first();

        // Cegah menambahkan diri sendiri
        if ($targetUser->id === $request->user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menambahkan diri sendiri'
            ], 422);
        }

        // Cek apakah user sudah menjadi anggota
        $exists = WalletMember::where('wallet_id', $wallet->id)
            ->where('user_id', $targetUser->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User sudah menjadi anggota dompet ini'
            ], 422);
        }

        $member = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $targetUser->id,
            'role' => 'member',
        ]);

        // Catat activity log
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'wallet_id' => $wallet->id,
            'action' => 'add_member',
            'description' => 'Menambahkan ' . $targetUser->name . ' ke dompet ' . $wallet->name,
        ]);

        return response()->json([
            'message' => 'Anggota berhasil ditambahkan',
            'data' => $member->load('user:id,name,email')
        ], 201);
    }

    /**
     * Hapus anggota dari dompet bersama
     */
    public function destroy(
        Request $request,
        Wallet $wallet,
        WalletMember $member
    ) {
        // Hanya owner yang bisa menghapus anggota
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Hanya pemilik dompet yang dapat menghapus anggota'
            ], 403);
        }

        // Tidak bisa menghapus diri sendiri (owner)
        if ($member->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus diri sendiri dari dompet'
            ], 422);
        }

        $memberName = $member->user->name ?? 'Unknown';

        $member->delete();

        // Catat activity log
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'wallet_id' => $wallet->id,
            'action' => 'remove_member',
            'description' => 'Menghapus ' . $memberName . ' dari dompet ' . $wallet->name,
        ]);

        return response()->json([
            'message' => 'Anggota berhasil dihapus'
        ]);
    }

    /**
     * Riwayat aktivitas dompet bersama
     */
    public function activity(Request $request, Wallet $wallet)
    {
        $isMember = $this->hasAccess(
            $wallet,
            $request->user()->id
        );

        if (!$isMember) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $logs = ActivityLog::with('user:id,name')
            ->where('wallet_id', $wallet->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    /**
     * Cek apakah user punya akses ke wallet
     */
    private function hasAccess(Wallet $wallet, int $userId): bool
    {
        if ($wallet->user_id === $userId) {
            return true;
        }

        return WalletMember::where('wallet_id', $wallet->id)
            ->where('user_id', $userId)
            ->exists();
    }
}

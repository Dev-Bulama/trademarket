<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function pending()
    {
        $pageTitle   = 'Pending Withdrawals';
        $withdrawals = $this->withdrawalData('pending');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function approved()
    {
        $pageTitle   = 'Approved Withdrawals';
        $withdrawals = $this->withdrawalData('approved');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function rejected()
    {
        $pageTitle   = 'Rejected Withdrawals';
        $withdrawals = $this->withdrawalData('rejected');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function log()
    {
        $pageTitle      = 'Withdrawals Log';
        $withdrawalData = $this->withdrawalData($scope = null, $summery = true);

        $withdrawals    = $withdrawalData['data'];
        $summery        = $withdrawalData['summery'];
        $successful     = $summery['successful'];
        $pending        = $summery['pending'];
        $rejected       = $summery['rejected'];
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals','successful','pending','rejected'));
    }

    protected function withdrawalData($scope = null, $summery = false){
        if ($scope) {
            $withdrawals = Withdrawal::$scope();
        }else{
            $withdrawals = Withdrawal::where('withdrawals.status','!=',Status::PAYMENT_INITIATE);
        }

        $withdrawals = $withdrawals->searchable(['trx','user:username']);
        $request     = request();

        if ($request->date) {
            $date        = explode('-', $request->date);
            $startDate   = Carbon::parse(trim($date[0]))->format('Y-m-d');
            $endDate     = @$date[1] ? Carbon::parse(trim(@$date[1]))->format('Y-m-d') : $startDate;
            $withdrawals = $withdrawals->whereDate('withdrawals.created_at', '>=', $startDate)->whereDate('withdrawals.created_at', '<=', $endDate);
        }

        if ($request->method) {
            $withdrawals = $withdrawals->where('method_id',$request->method);
        }
        if (!$summery) {
            return $withdrawals->with(['user','method','wallet.currency'])->orderBy('id','desc')->paginate(getPaginate());
        }else{
            $summationQuery    = (clone $withdrawals)->join('currencies', 'withdrawals.currency', 'currencies.symbol');
            $successfulSummery = (clone $summationQuery)->where('withdrawals.status',Status::PAYMENT_SUCCESS)->sum(DB::raw('currencies.rate * withdrawals.amount'));
            $pendingSummery    = (clone $summationQuery)->where('withdrawals.status',Status::PAYMENT_PENDING)->sum(DB::raw('currencies.rate * withdrawals.amount'));
            $rejectedSummery   = (clone $summationQuery)->where('withdrawals.status',Status::PAYMENT_REJECT)->sum(DB::raw('currencies.rate * withdrawals.amount'));

            return [
                'data'    => $withdrawals->with(['user','method'])->orderBy('id','desc')->paginate(getPaginate()),
                'summery' => [
                    'successful' => $successfulSummery,
                    'pending'    => $pendingSummery,
                    'rejected'   => $rejectedSummery,
                ]
            ];
        }
    }

    public function details($id)
    {
        $withdrawal = Withdrawal::where('id',$id)->where('status', '!=', Status::PAYMENT_INITIATE)->with(['user','method'])->firstOrFail();
        $pageTitle  = $withdrawal->user->username.' Withdraw Requested ' . showAmount($withdrawal->amount) . ' '.gs('cur_text');
        $details    = $withdrawal->withdraw_information ? json_encode($withdrawal->withdraw_information) : null;

        return view('admin.withdraw.detail', compact('pageTitle', 'withdrawal','details'));
    }

    public function approve(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $withdraw                 = Withdrawal::where('id',$request->id)->where('status',Status::PAYMENT_PENDING)->with('user','wallet')->firstOrFail();
        $withdraw->status         = Status::PAYMENT_SUCCESS;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        notify($withdraw->user, 'WITHDRAW_APPROVE', [
            'method_name'     => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount'   => showAmount($withdraw->final_amount),
            'amount'          => showAmount($withdraw->amount),
            'charge'          => showAmount($withdraw->charge),
            'rate'            => showAmount($withdraw->rate),
            'trx'             => $withdraw->trx,
            'admin_details'   => $request->details,
            'wallet_name'     => @$withdraw->wallet->symbol
        ]);

        $notify[] = ['success', 'Withdrawal approved successfully'];
        return to_route('admin.withdraw.pending')->withNotify($notify);
    }


    public function reject(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id',$request->id)->where('status',Status::PAYMENT_PENDING)->with('user','wallet')->firstOrFail();

        $withdraw->status         = Status::PAYMENT_REJECT;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        $user = $withdraw->user;

        $wallet           = $withdraw->wallet;
        $wallet->balance += $withdraw->amount;
        $wallet->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $withdraw->user_id;
        $transaction->amount       = $withdraw->amount;
        $transaction->post_balance = $wallet->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '+';
        $transaction->remark       = 'withdraw_reject';
        $transaction->details      = showAmount($withdraw->amount) . ' ' . gs('cur_text') . ' Refunded from withdrawal rejection';
        $transaction->trx          = $withdraw->trx;
        $transaction->wallet_id    = $wallet->id;
        $transaction->save();

        notify($user, 'WITHDRAW_REJECT', [
            'method_name'     => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount'   => showAmount($withdraw->final_amount),
            'amount'          => showAmount($withdraw->amount),
            'charge'          => showAmount($withdraw->charge),
            'rate'            => showAmount($withdraw->rate),
            'trx'             => $withdraw->trx,
            'post_balance'    => showAmount($user->balance),
            'admin_details'   => $request->details,
            'wallet_name'     => $wallet->symbol
        ]);

        $notify[] = ['success', 'Withdrawal rejected successfully'];
        return to_route('admin.withdraw.pending')->withNotify($notify);
    }

}

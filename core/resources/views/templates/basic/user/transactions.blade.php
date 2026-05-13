@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center gy-2">
        <div class="col-lg-12">
            <div class="show-filter mb-3 text-end">
                <button type="button" class="btn btn--base showFilterBtn btn--sm"><i class="las la-filter"></i>
                    @lang('Filter')</button>
            </div>
            <div class="card responsive-filter-card mb-4">
                <div class="card-body">
                    <form action="">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label class="form-label">@lang('Transaction Number')</label>
                                <input type="text" name="search" value="{{ request()->search }}"
                                    class="form-control form--control">
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label">@lang('Currency')</label>
                                <select name="symbol" class="form-select form--control select2">
                                    <option value="">@lang('All')</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->symbol }}" @selected(request()->symbol == $currency->symbol)>
                                                {{ __($currency->symbol) }}
                                            </option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label">@lang('Type')</label>
                                <select name="trx_type" class="form-select form--control">
                                    <option value="">@lang('All')</option>
                                    <option value="+" @selected(request()->trx_type == '+')>@lang('Plus')</option>
                                    <option value="-" @selected(request()->trx_type == '-')>@lang('Minus')</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label">@lang('Remark')</label>
                                <select class="form-select form--control" name="remark">
                                    <option value="">@lang('Any')</option>
                                    @foreach ($remarks as $remark)
                                        <option value="{{ $remark->remark }}" @selected(request()->remark == $remark->remark)>
                                            {{ __(keyToTitle($remark->remark)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--base w-100"><i class="las la-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="table-wrapper">
                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Currency | Wallet')</th>
                            <th>@lang('Transacted')</th>
                            <th>@lang('Trx')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Post Balance')</th>
                            <th>@lang('Detail')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            @php
                                $curSymbol = @$trx->wallet->currency->symbol;
                            @endphp
                            <tr>
                                <td>
                                    <div class="text-end text-lg-start">
                                        <span>{{ $curSymbol }}</span>
                                        <br>
                                        <small>{{ @$trx->wallet->name }} | {{ __(strToUpper(@$trx->wallet->type_text)) }} </small> 
                                    </div>
                                </td>
                                <td>
                                    <div class="text-end text-lg-start">
                                        {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                    </div>
                                </td>
                                <td>{{ $trx->trx }}</td>
                                <td>
                                    <span
                                        class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                        {{ $trx->trx_type }} {{ showAmount($trx->amount) }}
                                        {{ $curSymbol }}
                                    </span>
                                </td>
                                <td> {{ showAmount($trx->post_balance) }}
                                    {{ $curSymbol }}
                                </td>
                                <td>{{ __($trx->details) }}</td>
                            </tr>
                        @empty
                            @php echo userTableEmptyMessage('transactions') @endphp
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                {{ paginateLinks($transactions) }}
            @endif
        </div>
    </div>
@endsection

@push('topContent')
    <h4 class="mb-4">{{ __($pageTitle) }}</h4>
@endpush



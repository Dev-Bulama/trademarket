@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="pt-120 pb-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card custom--card">
                        <div class="card-body">
                            <div class="alert alert-primary mb-4" role="alert">
                                <strong>
                                    @lang('Complete your profile')
                                </strong>
                                <p>@lang('You need to complete your profile by providing below information.')</p>
                            </div>
                            <form method="POST" action="{{ route('user.data.submit') }}">
                                @csrf
                                <div class="row">
                                    @if (!$user->email)
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label">@lang('E-Mail Address')</label>
                                                <input type="email" class="form-control form--control checkUser" name="email"
                                                    value="{{ old('email') }}" required>
                                            </div>
                                        </div>
                                    @endif
                                    @if (!$user->mobile)
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Country')</label>
                                                <select name="country" class="form--control register-select">
                                                    @foreach ($countries as $key => $country)
                                                        <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}"
                                                            data-code="{{ $key }}">
                                                            {{ __($country->country) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Mobile')</label>
                                                <div class="input-group ">
                                                    <span class="input-group-text mobile-code">
                                                    </span>
                                                    <input type="hidden" name="mobile_code">
                                                    <input type="hidden" name="country_code">
                                                    <input type="number" name="mobile" value="{{ old('mobile') }}"
                                                        class="form-control form--control checkUser" required>
                                                </div>
                                                <small class="text-danger mobileExist"></small>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('First Name')</label>
                                        <input type="text" class="form-control form--control" name="firstname" required value="{{ old('firstname') }}" required>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('Last Name')</label>
                                        <input type="text" class="form-control form--control" name="lastname" required value="{{ old('lastname') }}"
                                            required>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('Address')</label>
                                        <input type="text" class="form-control form--control" name="address" value="{{ old('address') }}">
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('State')</label>
                                        <input type="text" class="form-control form--control" name="state" value="{{ old('state') }}">
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('Zip Code')</label>
                                        <input type="text" class="form-control form--control" name="zip" value="{{ old('zip') }}">
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('City')</label>
                                        <input type="text" class="form-control form--control" name="city" value="{{ old('city') }}">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn--base w-100">
                                    @lang('Submit')
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade custom--modal" id="existModalCenter" tabindex="-1" role="dialog" aria-labelledby="existModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h5>
                    <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <h6 class="text-center">@lang('You already have an account please Login ')</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark btn--sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <a href="{{ route('user.logout') }}" class="btn btn--base btn--sm">@lang('Login')</a>
                </div>
            </div>
        </div>
    </div>

@endsection


@if (!$user->email || !$user->mobile)
    @push('script')
        <script>
            "use strict";
            (function($) {
                
                @if (!$user->mobile)
                    @if ($mobileCode)
                        $(`option[data-code={{ $mobileCode }}]`).attr('selected', '');
                    @endif
                    $('select[name=country]').change(function() {
                        $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                        $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                        $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
                    });
                    $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                    $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                    $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
                @endif

                $('.checkUser').on('focusout', function(e) {
                    var url = `{{ route('user.auth.checkUser') }}`;
                    var value = $(this).val();
                    var token = '{{ csrf_token() }}';
                    if ($(this).attr('name') == 'mobile') {
                        var mobile = `${$('.mobile-code').text().substr(1)}${value}`;
                        var data = {
                            mobile: mobile,
                            _token: token
                        }
                    }
                    if ($(this).attr('name') == 'email') {
                        var data = {
                            email: value,
                            _token: token
                        }
                    }
                    if ($(this).attr('name') == 'username') {
                        var data = {
                            username: value,
                            _token: token
                        }
                    }
                    $.post(url, data, function(response) {
                        if (response.data != false && response.type == 'email') {
                            $('#existModalCenter').modal('show');
                        } else if (response.data != false) {
                            $(`.${response.type}Exist`).text(`${response.type} already exist`);
                        } else {
                            $(`.${response.type}Exist`).text('');
                        }
                    });
                });
            })(jQuery);
        </script>
    @endpush
@endif

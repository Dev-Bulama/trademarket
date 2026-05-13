@extends($activeTemplate.'layouts.master')
@section('content')
<div class="row gy-4">
    <div class="col-lg-12">
        <div class="d-flex flex-between flex-wrap align-items-center">
            <h5 class="title mb-0">{{ __($pageTitle) }}</h5>
            <a href="{{route('ticket.index') }}" class="btn btn--base btn--sm outline">
                <i class="las la-list"></i> @lang('My Tickets')
            </a>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card custom--card">
            <div class="card-body">
                <form  action="{{route('ticket.store')}}"  method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-md-6 form-group">
                            <label class="form-label">@lang('Name')</label>
                            <input type="text" name="name" value="{{@$user->firstname . ' '.@$user->lastname}}" class="form-control form--control" required readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">@lang('Email Address')</label>
                            <input type="email"  name="email" value="{{@$user->email}}" class="form-control form--control" required readonly>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label">@lang('Subject')</label>
                            <input type="text" name="subject" value="{{old('subject')}}" class="form-control form--control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">@lang('Priority')</label>
                            <select name="priority" class="form-control form--control" required>
                                <option value="3">@lang('High')</option>
                                <option value="2">@lang('Medium')</option>
                                <option value="1">@lang('Low')</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">@lang('Message')</label>
                            <textarea name="message" id="inputMessage" rows="6" class="form-control form--control" required>{{old('message')}}</textarea>
                        </div>
                        <div class="text-end ">
                            <a href="javascript:void(0)" class="text-muted addFile"><i class="las la-paperclip"></i> <span class="attachment-text">@lang('Attach Files')</span></a>
                        </div>
                        <div class="form--group col-sm-12 attachment-wrapper d-none">
                            <div class="file-upload"></div>
                            <div id="fileUploadsContainer"></div>
                            <p class="ticket-attachments-message text-muted mt-2">
                                @lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'),
                                .@lang('pdf'), .@lang('doc'), .@lang('docx'). &nbsp;
                                <small class="text--danger">@lang('Max 5 files can be uploaded'). @lang('Maximum upload size is') {{ ini_get('upload_max_filesize') }}
                                </small>
                            </p>

                        </div>
                        <div class="col-12">
                            <button class="btn btn--base w-100" type="submit" id="recaptcha" ><i class="fa fa-paper-plane"></i>&nbsp;@lang('Submit')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection



@push('script')
    <script>
        (function($) {
            "use strict";
            var fileAdded = 0;
            $('.addFile').on('click', function() {
                if (fileAdded >= 5) {
                    notify('error', 'You\'ve added maximum number of file');
                    return false;
                }

                fileAdded++;
                $("#fileUploadsContainer").append(`
                    <div class="d-flex gap-2 mt-3">
                        <input type="file" name="attachments[]" class="form-control form--control" required />
                        <button type="button" class="btn btn--danger remove-btn btn--sm"><i class="fa fa-times"></i></button>
                    </div>
                `);

                attachmentInfo();

            });


            $(document).on('click', '.remove-btn', function() {
                fileAdded--;
                $(this).closest('.d-flex').remove();
                attachmentInfo();
            });

            function attachmentInfo() {
                if ($('[name="attachments[]"]').length > 0) {
                    $('.attachment-text').text('Add More');
                    $('.attachment-wrapper').removeClass('d-none');
                } else {
                    $('.attachment-text').text('Attach Files');
                    $('.attachment-wrapper').addClass('d-none');
                }
            }
        })(jQuery);
    </script>
@endpush

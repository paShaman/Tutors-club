<!-- Modal -->
<div class="modal fade" id="{{ $modalId }}" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> {{ $modalTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @yield('modal-body')
            </div>
            <div class="modal-footer">
                @yield('modal-button')

                @hasSection('modal-close')
                    @yield('modal-close')
                @else
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> {{ lng('btn.cancel') }}</button>
                @endif
            </div>
        </div>
    </div>
</div>
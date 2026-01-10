@extends('vein::layout')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $model->menuName() }}</h1>
        <a href="{{ route('vein.add', ['node' => $node]) }}" class="btn btn-primary">新規</a>
    </div>
    <section class="section">
        @foreach ($taxonomies as $taxonomy)
        <form class="row align-items-end mb-3 __edit_form" data-id="{{ $taxonomy->getKey() }}">
            @csrf
            <div class="col" style="flex-basis: 20px;">≡</div>
            <div class="col" style="flex-basis: calc(100% - 280px);">
                <div class="row">
                    @foreach ($editFields as $editField)
                    {!! $editField->renderColumn($taxonomy) !!}
                    @endforeach
                </div>
            </div>
            <div class="col" style="flex-basis: 200px;">
                <button class="btn btn-primary">EDIT</button>
                <button class="btn btn-outline-danger __delete_button" type="button">DELETE</button>
            </div>
        </form>
        @endforeach
        <form class="row align-items-end mb-3 __add_form">
            @csrf
            <div class="col __col_handle" style="flex-basis: 20px;"></div>
            <div class="col" style="flex-basis: calc(100% - 280px);">
                <div class="row">
                    @foreach ($editFields as $editField)
                        {!! $editField->renderColumn() !!}
                    @endforeach
                </div>
            </div>
            <div class="col __col_action" style="flex-basis: 200px;">
                <button class="btn btn-primary">ADD</button>
            </div>
        </form>
    </section>
</div>

<script>
const add_api = '{{ route("vein.add", [$node]) }}';
const edit_api = '{{ route("vein.edit", [$node, 9999]) }}';
const delete_api = '{{ route("vein.delete", [$node, 9999]) }}';
</script>
<script>
// TODO ソート

$(document).on('submit', '.__edit_form', function () {
    try {
        const key = $(this).data('id');
        const api = edit_api.replace('9999', key);
        const payload = new FormData($(this)[0]);

        fetch(api, {
            method: 'POST',
            body: payload,
        })
        .then(response => response.json())
        .then(data => {

        })
        .catch((error) => {
            console.error(error);
        });

    } catch (e) {
        console.error(e);
    }

    return false;
});

$(document).on('submit', '.__add_form', function () {
    try {
        const payload = new FormData($(this)[0]);

        fetch(add_api, {
            method: 'POST',
            body: payload,
        })
        .then(response => response.json())
        .then(data => {
            const clone = $(this).clone();
            clone.addClass('__edit_form');
            clone.removeClass('__add_form');
            clone.data('id', data.key);
            clone.find('.__col_action').empty();
            clone.find('.__col_action').append('<button class="btn btn-primary">EDIT</button>');
            clone.find('.__col_action').append('<button class="btn btn-outline-danger __delete_button" type="button">DELETE</button>');
            clone.find('.__col_handle').text('≡');
            clone.insertBefore($(this));

            $(this)[0].reset();
        })
        .catch((error) => {
            console.error(error);
        });

    } catch (e) {
        console.error(e);
    }

    return false;
});

$(document).on('click', '.__delete_button', function () {
    try {
        const $form = $(this).closest('form');
        const key = $form.data('id');
        const api = delete_api.replace('9999', key);
        const payload = new FormData($form[0]);

        fetch(api, {
            method: 'POST',
            body: payload,
        })
        .then(response => response.json())
        .then(data => {
            $form.remove();
        })
        .catch((error) => {
            console.error(error);
        });

    } catch (e) {
        console.error(e);
    }

    return false;
});

</script>
@endsection

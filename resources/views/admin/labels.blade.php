@extends('layouts.app')

@section('title', 'Labels — MailDesk')

@section('content')
    <div class="pad">
        <div class="card">
            <h3>Create a label</h3>
            <form method="post" action="{{ route('labels.store') }}" class="label-create">
                @csrf
                <input class="f" name="name" maxlength="60" placeholder="e.g. Urgent, Quote sent, Paid" required>
                <div class="swatches">
                    @foreach(\App\Models\Label::COLORS as $c)
                        <label class="swatch" style="--sw: {{ $c }}">
                            <input type="radio" name="color" value="{{ $c }}" {{ $loop->first ? 'checked' : '' }}><span></span>
                        </label>
                    @endforeach
                </div>
                <button class="btn">Create</button>
            </form>
        </div>

        <div class="card">
            <h3>Labels</h3>
            @forelse($labels as $lb)
                <form method="post" action="{{ route('labels.update', $lb) }}" class="label-edit-row">
                    @csrf
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $lb->color }}" style="flex:0 0 auto"><path d="M17.63 5.84A2 2 0 0 0 16 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 1.63-.84L22 12l-4.37-6.16z"/></svg>
                    <input class="f" name="name" value="{{ $lb->name }}" maxlength="60" required>
                    <div class="swatches">
                        @foreach(\App\Models\Label::COLORS as $c)
                            <label class="swatch" style="--sw: {{ $c }}">
                                <input type="radio" name="color" value="{{ $c }}" {{ $lb->color === $c ? 'checked' : '' }}><span></span>
                            </label>
                        @endforeach
                    </div>
                    <a class="label-count" href="{{ route('inbox', ['label' => $lb->id]) }}">{{ $lb->threads_count }} conversation{{ $lb->threads_count === 1 ? '' : 's' }}</a>
                    <button class="btn ghost">Save</button>
                    <button class="btn danger" type="submit" formaction="{{ route('labels.delete', $lb) }}"
                            onclick="return confirm('Delete the label “{{ addslashes($lb->name) }}”? It is removed from all conversations, but the emails stay.')">Delete</button>
                </form>
            @empty
                <p style="color:var(--text-2)">No labels yet. Create one above, or from any conversation with “Add label”.</p>
            @endforelse
        </div>
    </div>
@endsection

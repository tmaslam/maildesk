<?php

namespace App\Http\Controllers;

use App\Models\EmailThread;
use App\Models\Label;
use App\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LabelController extends Controller
{
    /** Admin page: rename, recolour, delete labels. */
    public function index()
    {
        return view('admin.labels', [
            'labels'    => Label::withCount('threads')->orderBy('name')->get(),
            'mailboxes' => Mailbox::sidebar(),
        ]);
    }

    /** Anyone on the team can create a label (from a conversation or the sidebar). */
    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:60', 'unique:labels,name'],
            'color' => ['nullable', Rule::in(Label::COLORS)],
        ], ['name.unique' => 'A label with this name already exists.']);

        $label = Label::create([
            'name'       => $data['name'],
            'color'      => $data['color'] ?? Label::nextColor(),
            'created_by' => $request->user()->id,
        ]);

        if ($request->filled('thread_id')) {
            EmailThread::find($request->integer('thread_id'))?->labels()->syncWithoutDetaching([$label->id]);
        }

        return $request->expectsJson()
            ? response()->json(['label' => $label->only('id', 'name', 'color')])
            : back()->with('status', "Label \"{$label->name}\" created.");
    }

    public function update(Request $request, Label $label)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:60', Rule::unique('labels', 'name')->ignore($label->id)],
            'color' => ['required', Rule::in(Label::COLORS)],
        ], ['name.unique' => 'A label with this name already exists.']);
        $label->update($data);
        return back()->with('status', 'Label saved.');
    }

    public function destroy(Label $label)
    {
        $label->delete(); // removed from every conversation; the emails stay
        return back()->with('status', "Label \"{$label->name}\" deleted. The emails themselves are kept.");
    }

    /** Set the exact labels of one conversation (checkbox menu in the thread view). */
    public function setForThread(Request $request, EmailThread $thread)
    {
        $data = $request->validate([
            'labels'   => ['array'],
            'labels.*' => ['integer', 'exists:labels,id'],
        ]);
        $thread->labels()->sync($data['labels'] ?? []);
        $labels = $thread->labels()->get(['labels.id', 'labels.name', 'labels.color']);

        return $request->expectsJson()
            ? response()->json(['labels' => $labels->map(fn ($l) => $l->only('id', 'name', 'color'))])
            : back()->with('status', 'Labels updated.');
    }

    /** Admin bulk action from the inbox checkboxes: add or remove one label. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'      => ['required', 'array'],
            'ids.*'    => ['integer'],
            'label_id' => ['required', 'integer', 'exists:labels,id'],
            'mode'     => ['nullable', Rule::in(['add', 'remove'])],
        ]);
        $label = Label::findOrFail($data['label_id']);
        $ids = EmailThread::whereIn('id', $data['ids'])->pluck('id')->all();

        if (($data['mode'] ?? 'add') === 'remove') {
            $label->threads()->detach($ids);
            $msg = "Label \"{$label->name}\" removed from " . count($ids) . ' conversation(s).';
        } else {
            $label->threads()->syncWithoutDetaching($ids);
            $msg = "Label \"{$label->name}\" added to " . count($ids) . ' conversation(s).';
        }
        return back()->with('status', $msg);
    }
}

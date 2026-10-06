@if(($showEdit ?? false) && isset($row['entry_id']))
<a href="{{ route('journal-entries.edit', ['journal_entry' => $row['entry_id'], 'return_to' => request()->getRequestUri()]) }}" class="inline-block ml-2 text-blue-600 underline whitespace-nowrap" aria-label="{{ $row['date']->format('Y年n月j日') }}の振替伝票を編集">編集</a>
@endif

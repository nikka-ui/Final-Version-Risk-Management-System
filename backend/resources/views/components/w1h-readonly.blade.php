@props([
  'w' => [],
])

@php
  $items = [
    'what' => 'What happened?',
    'why' => 'Why did it happen?',
    'where' => 'Where did it occur?',
    'when' => 'When did it occur?',
    'who' => 'Who was involved?',
    'how' => 'How was it discovered?',
  ];
@endphp

<div {{ $attributes->class(['w1h-grid', 'w1h-grid--readonly']) }}>
  @foreach ($items as $key => $label)
    @php
      $answer = trim((string) ($w[$key] ?? ''));
      $filled = $answer !== '';
    @endphp
    <article class="w1h-item w1h-item--{{ $key }}">
      <h3 class="w1h-badge{{ $filled ? ' is-filled' : ' is-empty' }}">
        <span class="w1h-badge__dot" aria-hidden="true"></span>
        {{ $label }}
      </h3>
      <p>{{ $filled ? $answer : '—' }}</p>
    </article>
  @endforeach
</div>

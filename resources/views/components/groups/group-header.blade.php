<div class="bg-offside-primary bg-opacity-99 p-1 mb-4 fixed left-0 right-0 w-full" style="z-index: 1000; margin-top: 2.2rem;">
    <a href="{{ route('rankings.group', $group) }}" class="text-offside-light">
        <marquee behavior="scroll" direction="left" scrollamount="5">
            @foreach($group->users->sortByDesc('total_points')->take(3) as $index => $user)
                <span class="font-bold text-offside-light">
                    @if($index === 0) 🥇 @elseif($index === 1) 🥈 @elseif($index === 2) 🥉 @endif
                    {{ $user->name }} ({{ $user->total_points ?? 0 }} puntos)
                </span>
                @if(!$loop->last)
                    <span class="mx-2">|</span>
                @endif
            @endforeach
        </marquee>
    </a>
</div>

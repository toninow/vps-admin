<div class="mpsfp-panel p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">
                <span>Proyectos</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span>{{ $project->name }}</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-[#E6007E]">{{ $label }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <h2 class="font-headline text-2xl font-extrabold tracking-tight text-mptext">{{ $title }}</h2>
                <span class="mpsfp-pill status-pink">Estás en {{ $label }}</span>
            </div>
            @if (!empty($subtitle))
                <p class="mt-2 max-w-4xl text-sm leading-6 text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn-secondary w-full sm:w-auto">Volver al proyecto</a>
    </div>
</div>

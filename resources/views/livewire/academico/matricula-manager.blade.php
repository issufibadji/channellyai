<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-1">{{ $turma->nome }}</h1>
    <p class="text-sm text-text-secondary mb-6">Matrícula de alunos</p>

    <x-card class="mb-6">
        <form wire:submit="matricular" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-text-primary">Matricular aluno</label>
                <select wire:model="alunoId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">Selecione um aluno</option>
                    @foreach ($candidatos as $candidato)
                        <option value="{{ $candidato->id }}">{{ $candidato->name }} ({{ $candidato->email }})</option>
                    @endforeach
                </select>
                @error('alunoId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>
            <x-button type="submit">Matricular</x-button>
        </form>
    </x-card>

    <x-table :headers="['Nome', 'E-mail', 'Data matrícula', 'Status', 'Ações']">
        @foreach ($alunos as $aluno)
            <tr>
                <td class="px-4 py-3">{{ $aluno->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $aluno->email }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ \Illuminate\Support\Carbon::parse($aluno->pivot->data_matricula)->format('d/m/Y') }}</td>
                <td class="px-4 py-3"><x-badge variant="primary">{{ $aluno->pivot->status }}</x-badge></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button
                        wire:click="desmatricular({{ $aluno->id }})"
                        wire:confirm="Remover a matrícula deste aluno?"
                        class="text-danger hover:underline text-sm"
                    >
                        Desmatricular
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>
</div>

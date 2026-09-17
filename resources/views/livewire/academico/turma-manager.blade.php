<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Turmas</h1>
        <x-button wire:click="create">+ Turma</x-button>
    </div>

    <x-table :headers="['Nome', 'Curso', 'Professor', 'Alunos', 'Ativo', 'Ações']">
        @foreach ($turmas as $turma)
            <tr>
                <td class="px-4 py-3">{{ $turma->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $turma->curso->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $turma->professor->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $turma->alunos_count }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$turma->ativo ? 'success' : 'default'">{{ $turma->ativo ? 'Sim' : 'Não' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <a href="{{ route('academico.turmas.matricula', $turma) }}" class="text-primary hover:underline text-sm">Matrícula</a>
                    <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="text-primary hover:underline text-sm">Conteúdo</a>
                    <button wire:click="edit({{ $turma->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $turma->id }})"
                        wire:confirm="Remover esta turma?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>

    <x-modal name="turma-form" title="Turma">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Curso</label>
                <select wire:model="cursoId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">Selecione um curso</option>
                    @foreach ($cursos as $curso)
                        <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                    @endforeach
                </select>
                @error('cursoId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Professor responsável</label>
                <select wire:model="professorId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">Selecione um professor</option>
                    @foreach ($professores as $professor)
                        <option value="{{ $professor->id }}">{{ $professor->name }}</option>
                    @endforeach
                </select>
                @error('professorId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="nome" placeholder="ex.: Turma Manhã" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('nome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Data início</label>
                    <input type="date" wire:model="dataInicio" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Data fim</label>
                    <input type="date" wire:model="dataFim" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                Ativa
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>

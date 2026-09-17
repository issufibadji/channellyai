<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Meus Alunos</h1>

    <x-table :headers="['Nome', 'E-mail', 'Turma(s)']">
        @foreach ($alunos as $aluno)
            <tr>
                <td class="px-4 py-3">{{ $aluno->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $aluno->email }}</td>
                <td class="px-4 py-3">
                    @foreach ($aluno->turmasMatriculadas as $turma)
                        <x-badge variant="primary">{{ $turma->nome }}</x-badge>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </x-table>
</div>

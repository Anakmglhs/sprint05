<?php

namespace App\Policies;

use App\Models\Pergunta;
use App\Models\User;

class PerguntaPolicy
{
    /**
     * TICKET 2 (Sprint 05): só o autor da pergunta OU o dono do evento
     * (a que a pergunta pertence) podem excluí-la.
     */
    public function delete(User $user, Pergunta $pergunta): bool
    {
        $ehAutor = $pergunta->user_id !== null
            && (int) $user->id === (int) $pergunta->user_id;

        $donoDoEventoId = $pergunta->evento?->user_id;
        $ehDonoDoEvento = $donoDoEventoId !== null
            && (int) $user->id === (int) $donoDoEventoId;

        return $ehAutor || $ehDonoDoEvento;
    }
}

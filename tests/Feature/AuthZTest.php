<?php

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;

function cenario(): array
{
    $dono   = User::factory()->create();
    $autor  = User::factory()->create();
    $outro  = User::factory()->create();
    $evento = Evento::create(['titulo' => 'Evento AuthZ', 'user_id' => $dono->id]);
    $pergunta = Pergunta::create([
        'evento_id' => $evento->id,
        'user_id'   => $autor->id,
        'texto'     => 'Pergunta escrita pelo autor',
        'status'    => 'pendente',
    ]);

    return compact('dono', 'autor', 'outro', 'evento', 'pergunta');
}

it('policy: autor e dono do evento podem excluir; outro usuário não', function () {
    ['dono' => $dono, 'autor' => $autor, 'outro' => $outro, 'pergunta' => $pergunta] = cenario();

    expect($autor->can('delete', $pergunta))->toBeTrue();
    expect($dono->can('delete', $pergunta))->toBeTrue();
    expect($outro->can('delete', $pergunta))->toBeFalse();
});

it('o autor consegue excluir a própria pergunta', function () {
    ['autor' => $autor, 'pergunta' => $pergunta] = cenario();

    $this->actingAs($autor)->delete(route('perguntas.destroy', $pergunta->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('perguntas', ['id' => $pergunta->id]);
});

it('o dono do evento consegue excluir pergunta de outro usuário', function () {
    ['dono' => $dono, 'pergunta' => $pergunta] = cenario();

    $this->actingAs($dono)->delete(route('perguntas.destroy', $pergunta->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('perguntas', ['id' => $pergunta->id]);
});

it('outro usuário recebe 403 ao forçar a URL de exclusão', function () {
    ['outro' => $outro, 'pergunta' => $pergunta] = cenario();

    $this->actingAs($outro)->delete(route('perguntas.destroy', $pergunta->id))
        ->assertForbidden();

    $this->assertDatabaseHas('perguntas', ['id' => $pergunta->id]);
});

it('visitante é redirecionado para o login ao tentar excluir', function () {
    ['pergunta' => $pergunta] = cenario();

    $this->delete(route('perguntas.destroy', $pergunta->id))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('perguntas', ['id' => $pergunta->id]);
});

it('o botão Excluir Pergunta aparece para autor e dono, e some para os demais', function () {
    ['dono' => $dono, 'autor' => $autor, 'outro' => $outro, 'evento' => $evento] = cenario();
    $url = route('eventos.show', $evento->id);

    $this->actingAs($autor)->get($url)->assertOk()->assertSee('Excluir Pergunta');
    $this->actingAs($dono)->get($url)->assertOk()->assertSee('Excluir Pergunta');
    $this->actingAs($outro)->get($url)->assertOk()->assertDontSee('Excluir Pergunta');
});

it('o botão não aparece para visitantes', function () {
    ['evento' => $evento] = cenario();

    $this->get(route('eventos.show', $evento->id))->assertOk()->assertDontSee('Excluir Pergunta');
});

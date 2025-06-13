<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OpenAIService;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIServiceTest extends TestCase
{
    public function test_verify_match_results()
    {
        // Mock directamente sobre el Facade
        OpenAI::shouldReceive('chat->create')
            ->once()
            ->andReturn((object)[
                'choices' => [
                    (object)[
                        'message' => (object)[
                            'content' => json_encode([
                                'respuestas' => [
                                    [
                                        'pregunta' => '¿Quién ganará el partido?',
                                        'respuesta_correcta' => 'Manchester United'
                                    ]
                                ]
                            ])
                        ]
                    ]
                ]
            ]);

        $service = new OpenAIService();

        $match = [
            'homeTeam' => 'Manchester United',
            'awayTeam' => 'Liverpool',
            'score' => '2-1',
            'events' => 'Goles: Rashford (15\'), Salah (45\'), Fernandes (75\')'
        ];

        $questions = [
            [
                'title' => '¿Quién ganará el partido?',
                'options' => ['Manchester United', 'Liverpool', 'Empate']
            ]
        ];

        $result = $service->verifyMatchResults($match, $questions);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotEmpty($result);
        $this->assertEquals('Manchester United', $result[0]['respuesta_correcta']);
    }
}

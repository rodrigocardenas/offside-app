<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exceptions\FootballApiException;
use App\Exceptions\GroupAccessException;
use App\Exceptions\QuestionException;
use App\Exceptions\ApplicationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionsTest extends TestCase
{
    /** @test */
    public function football_api_exception_renders_correctly_for_web_requests()
    {
        $exception = new FootballApiException(
            'API Error occurred',
            ['api_endpoint' => '/fixtures', 'status_code' => 500]
        );

        $request = Request::create('/test', 'GET');
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
    }

    /** @test */
    public function football_api_exception_renders_json_for_api_requests()
    {
        $exception = new FootballApiException(
            'API Error occurred',
            ['api_endpoint' => '/fixtures', 'status_code' => 500]
        );

        $request = Request::create('/api/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(503, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Football API Error', $content['error']);
        $this->assertEquals('API Error occurred', $content['message']);
        $this->assertArrayHasKey('context', $content);
    }

    /** @test */
    public function group_access_exception_redirects_for_web_requests()
    {
        $exception = new GroupAccessException(
            'Access denied to group',
            1,
            1
        );

        $request = Request::create('/groups/1', 'GET');
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function group_access_exception_renders_json_for_api_requests()
    {
        $exception = new GroupAccessException(
            'Access denied to group',
            1,
            1
        );

        $request = Request::create('/api/groups/1', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Group Access Denied', $content['error']);
        $this->assertEquals(1, $content['group_id']);
        $this->assertEquals(1, $content['user_id']);
    }

    /** @test */
    public function question_exception_uses_predefined_messages()
    {
        $exception = new QuestionException('', 1, 1, 'already_answered');

        $this->assertEquals('Ya has respondido esta pregunta', $exception->getMessage());
        $this->assertEquals('already_answered', $exception->getReason());
    }

    /** @test */
    public function question_exception_renders_json_for_api_requests()
    {
        $exception = new QuestionException(
            'Custom question error',
            1,
            1,
            'invalid_answer'
        );

        $request = Request::create('/api/questions/1/answer', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Question Error', $content['error']);
        $this->assertEquals('Custom question error', $content['message']);
        $this->assertEquals(1, $content['question_id']);
        $this->assertEquals(1, $content['user_id']);
        $this->assertEquals('invalid_answer', $content['reason']);
    }

    /** @test */
    public function application_exception_logs_errors_and_renders_500_page()
    {
        $exception = new ApplicationException(
            'Application error occurred',
            'DATABASE_ERROR',
            ['table' => 'users', 'operation' => 'insert'],
            500
        );

        $request = Request::create('/test', 'GET');
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function application_exception_renders_json_for_api_requests()
    {
        $exception = new ApplicationException(
            'Application error occurred',
            'VALIDATION_ERROR',
            ['field' => 'email', 'value' => 'invalid'],
            422
        );

        $request = Request::create('/api/test', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $exception->render($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('VALIDATION_ERROR', $content['error']);
        $this->assertEquals('Application error occurred', $content['message']);
        $this->assertArrayHasKey('context', $content);
    }

    /** @test */
    public function exceptions_can_be_created_with_minimal_parameters()
    {
        $footballException = new FootballApiException('API Error');
        $this->assertEquals('API Error', $footballException->getMessage());
        $this->assertEmpty($footballException->getContext());

        $groupException = new GroupAccessException('Access denied');
        $this->assertEquals('Access denied', $groupException->getMessage());
        $this->assertNull($groupException->getGroupId());

        $questionException = new QuestionException('Question error');
        $this->assertEquals('Question error', $questionException->getMessage());
        $this->assertNull($questionException->getQuestionId());

        $appException = new ApplicationException('App error');
        $this->assertEquals('App error', $appException->getMessage());
        $this->assertEmpty($appException->getContext());
    }
}

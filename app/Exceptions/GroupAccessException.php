<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupAccessException extends Exception
{
    protected $groupId;
    protected $userId;

    public function __construct(string $message = "No tienes acceso a este grupo", int $groupId = null, int $userId = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->groupId = $groupId;
        $this->userId = $userId;
        parent::__construct($message, $code, $previous);
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Group Access Denied',
                'message' => $this->getMessage(),
                'group_id' => $this->groupId,
                'user_id' => $this->userId
            ], 403);
        }

        return redirect()->route('groups.index')->with('error', $this->getMessage());
    }
}

<?php

return [
    'gemini_fallback_enabled' => env('QUESTION_EVAL_GEMINI_FALLBACK', true),
    'gemini_fallback_grounding' => env('QUESTION_EVAL_GEMINI_FALLBACK_GROUNDING', true),
    'gemini_fallback_max_events' => env('QUESTION_EVAL_GEMINI_FALLBACK_MAX_EVENTS', 40),
];

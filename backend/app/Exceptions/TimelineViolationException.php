<?php

namespace App\Exceptions;

use Exception;

class TimelineViolationException extends Exception
{
    protected $actSection;
    protected $regulationRule;

    public function __construct(
        string $message = "", 
        string $actSection = "", 
        string $regulationRule = "", 
        int $code = 0, 
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->actSection = $actSection;
        $this->regulationRule = $regulationRule;
    }

    public function getActSection(): string
    {
        return $this->actSection;
    }

    public function getRegulationRule(): string
    {
        return $this->regulationRule;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'act_section' => $this->actSection,
            'regulation_rule' => $this->regulationRule,
            'error_type' => 'timeline_violation',
        ];
    }
}
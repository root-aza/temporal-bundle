<?php

/**
 * Temporal Bundle
 *
 * @author Vlad Shashkov <v.shashkov@pos-credit.ru>
 * @copyright Copyright (c) 2025, The Vanta
 */

declare(strict_types=1);

namespace Vanta\Integration\Symfony\Temporal\Interceptor;

use Symfony\Contracts\Service\ResetInterface;
use Temporal\Client\Workflow\WorkflowExecutionDescription;
use Temporal\DataConverter\ValuesInterface;
use Temporal\Interceptor\WorkflowClient\CancelInput;
use Temporal\Interceptor\WorkflowClient\DescribeInput;
use Temporal\Interceptor\WorkflowClient\GetResultInput;
use Temporal\Interceptor\WorkflowClient\QueryInput;
use Temporal\Interceptor\WorkflowClient\SignalInput;
use Temporal\Interceptor\WorkflowClient\SignalWithStartInput;
use Temporal\Interceptor\WorkflowClient\StartInput;
use Temporal\Interceptor\WorkflowClient\StartUpdateOutput;
use Temporal\Interceptor\WorkflowClient\TerminateInput;
use Temporal\Interceptor\WorkflowClient\UpdateInput;
use Temporal\Interceptor\WorkflowClient\UpdateWithStartInput;
use Temporal\Interceptor\WorkflowClient\UpdateWithStartOutput;
use Temporal\Interceptor\WorkflowClientCallsInterceptor;
use Temporal\Workflow\WorkflowExecution;

final class ProfilerWorkflowInterceptor implements WorkflowClientCallsInterceptor, ResetInterface
{
    public function __construct(
        private ?StartInput $startedWorkflow = null,
        private ?SignalInput $sendSignal = null,
        private ?UpdateInput $sendUpdate = null,
        private ?SignalWithStartInput $startedWorkflowWithSignal = null,
        private ?UpdateWithStartInput $startedWorkflowWithUpdate = null,
        private ?GetResultInput $getResultWorkflow = null,
        private ?QueryInput $sendQuery = null,
        private ?CancelInput $sendCancelWorkflow = null,
        private ?TerminateInput $sendTerminateWorkflow = null,
        private ?DescribeInput $sendDescribe = null,
    ) {
    }


    public function start(StartInput $input, callable $next): WorkflowExecution
    {
        $this->startedWorkflow = $input;

        return $next($input);
    }

    public function signal(SignalInput $input, callable $next): void
    {
        $this->sendSignal = $input;
    }

    public function update(UpdateInput $input, callable $next): StartUpdateOutput
    {
        $this->sendUpdate = $input;

        return $next($input);
    }

    public function signalWithStart(SignalWithStartInput $input, callable $next): WorkflowExecution
    {
        $this->startedWorkflowWithSignal = $input;

        return $next($input);
    }

    public function updateWithStart(UpdateWithStartInput $input, callable $next): UpdateWithStartOutput
    {
        $this->startedWorkflowWithUpdate = $input;

        return $next($input);
    }

    public function getResult(GetResultInput $input, callable $next): ?ValuesInterface
    {
        $this->getResultWorkflow = $input;

        return $next($input);
    }

    public function query(QueryInput $input, callable $next): ?ValuesInterface
    {
        $this->sendQuery = $input;

        return $next($input);
    }

    public function cancel(CancelInput $input, callable $next): void
    {
        $this->sendCancelWorkflow = $input;
    }

    public function terminate(TerminateInput $input, callable $next): void
    {
        $this->sendTerminateWorkflow = $input;
    }

    public function describe(DescribeInput $input, callable $next): WorkflowExecutionDescription
    {
        $this->sendDescribe = $input;

        return $next($input);
    }

    public function getStartedWorkflow(): StartInput
    {
        return $this->startedWorkflow;
    }

    public function reset(): void
    {
        $this->startedWorkflow           = null;
        $this->sendSignal                = null;
        $this->sendUpdate                = null;
        $this->startedWorkflowWithSignal = null;
        $this->startedWorkflowWithUpdate = null;
        $this->getResultWorkflow         = null;
        $this->sendQuery                 = null;
        $this->sendCancelWorkflow        = null;
        $this->sendTerminateWorkflow     = null;
        $this->sendDescribe              = null;
    }

    public function getSendSignal(): ?SignalInput
    {
        return $this->sendSignal;
    }

    public function getSendUpdate(): ?UpdateInput
    {
        return $this->sendUpdate;
    }

    public function getStartedWorkflowWithSignal(): ?SignalWithStartInput
    {
        return $this->startedWorkflowWithSignal;
    }

    public function getStartedWorkflowWithUpdate(): ?UpdateWithStartInput
    {
        return $this->startedWorkflowWithUpdate;
    }

    public function getGetResultWorkflow(): ?GetResultInput
    {
        return $this->getResultWorkflow;
    }

    public function getSendQuery(): ?QueryInput
    {
        return $this->sendQuery;
    }

    public function getSendCancelWorkflow(): ?CancelInput
    {
        return $this->sendCancelWorkflow;
    }

    public function getSendTerminateWorkflow(): ?TerminateInput
    {
        return $this->sendTerminateWorkflow;
    }

    public function getSendDescribe(): ?DescribeInput
    {
        return $this->sendDescribe;
    }
}

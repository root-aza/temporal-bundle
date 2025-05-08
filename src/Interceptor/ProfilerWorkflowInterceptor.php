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
use Temporal\Client\Update\WaitPolicy;
use Temporal\Client\Workflow\WorkflowExecutionDescription;
use Temporal\Client\WorkflowOptions;
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

/**
 * @phpstan-type StartedWorkflow array{
 *    workflowOptions: WorkflowOptions,
 *    workflowId: string,
 *    workflowType: string,
 *    workflowHeaders:  array<mixed>,
 *    signalArguments: array<mixed>
 * }
 *
 * @phpstan-type SendUpdate array{
 *   workflowType: string|null,
 *   workflowHeaders: array<mixed>,
 *   workflowArguments: array<mixed>,
 *   workflowRunId: string|null,
 *   workflowId:  string,
 *   updateId: string,
 *   updateName: string,
 *   firstExecutionRunId: string,
 *   resultType: mixed,
 *   waitPolicy: WaitPolicy
 * }
 *
 * @phpstan-type SendSignal array{
 *   workflowType: string|null,
 *   signal: string,
 *   workflowId: string,
 *   workflowRunId: string|null,
 *   signalArguments: array<mixed>
 * }
 *
 *
 * @phpstan-type StartedWorkflowWithSignal array{
 *   workflowType: string,
 *   workflowId: string,
 *   workflowOptions: WorkflowOptions,
 *   workflowArguments: array<mixed>,
 *   workflowHeaders: array<mixed>,
 *   signalArguments: array<mixed>,
 *   signal:string,
 * }
 *
 * @phpstan-type StartedWorkflowWithUpdate array{
 *    workflowType: string,
 *    workflowId: string,
 *    workflowRunId: string|null,
 *    workflowOptions: WorkflowOptions,
 *    workflowArguments: array<mixed>,
 *    workflowHeaders: array<mixed>,
 *    update: string,
 * }
 *
 *
 * @phpstan-type GetResultWorkflow array{
 *   workflowType: string|null,
 *   workflowId: string,
 *   workflowRunId: string|null,
 *   timeout: int|null,
 *   returnType: mixed
 * }
 *
 *
 * @phpstan-type SendQuery array{
 *  workflowType: string|null,
 *  workflowId: string,
 *  workflowRunId: string|null,
 *  query: string,
 *  queryArguments: array<mixed>
 * }
 *
 * @phpstan-type SendCancelWorkflow array{
 *   workflowId: string,
 *   workflowRunId: string|null
 * }
 *
 * @phpstan-type SendTerminateWorkflow array{
 *    workflowId: string,
 *    workflowRunId: string|null
 *  }
 *
 * @phpstan-type SendDescribe array{
 *   workflowId: string,
 *   workflowRunId: string|null
 * }
 *
 */
final class ProfilerWorkflowInterceptor implements WorkflowClientCallsInterceptor, ResetInterface
{
    /**
     * @param StartedWorkflow|null $startedWorkflow
     * @param SendSignal|null $sendSignal
     * @param SendUpdate|null $sendUpdate
     * @param StartedWorkflowWithSignal|null $startedWorkflowWithSignal
     * @param StartedWorkflowWithUpdate|null $startedWorkflowWithUpdate
     * @param GetResultWorkflow|null $getResultWorkflow
     * @param SendQuery|null $sendQuery
     * @param SendCancelWorkflow|null $sendCancelWorkflow
     * @param SendTerminateWorkflow|null $sendTerminateWorkflow
     * @param SendDescribe|null $sendDescribe
     */
    public function __construct(
        private ?array $startedWorkflow = null,
        private ?array $sendSignal = null,
        private ?array $sendUpdate = null,
        private ?array $startedWorkflowWithSignal = null,
        private ?array $startedWorkflowWithUpdate = null,
        private ?array $getResultWorkflow = null,
        private ?array $sendQuery = null,
        private ?array $sendCancelWorkflow = null,
        private ?array $sendTerminateWorkflow = null,
        private ?array $sendDescribe = null,
    ) {
    }


    public function start(StartInput $input, callable $next): WorkflowExecution
    {
        $this->startedWorkflow = [
            'workflowOptions' => $input->options,
            'workflowId'      => $input->workflowId,
            'workflowType'    => $input->workflowType,
            'workflowHeaders' => iterator_to_array($input->header->getIterator()),
            'signalArguments' => iterator_to_array($input->arguments->getValues()),
        ];

        return $next($input);
    }

    public function signal(SignalInput $input, callable $next): void
    {
        $this->sendSignal = [
           'workflowType'    => $input->workflowType,
           'signal'          => $input->signalName,
           'workflowId'      => $input->workflowExecution->getID(),
           'workflowRunId'   => $input->workflowExecution->getRunID(),
           'signalArguments' => iterator_to_array($input->arguments->getValues()),
        ];
    }

    public function update(UpdateInput $input, callable $next): StartUpdateOutput
    {
        $this->sendUpdate = [
            'workflowType'        => $input->workflowType,
            'workflowHeaders'     => iterator_to_array($input->header->getIterator()),
            'workflowArguments'   => iterator_to_array($input->arguments->getValues()),
            'workflowRunId'       => $input->workflowExecution->getRunID(),
            'workflowId'          => $input->workflowExecution->getID(),
            'updateId'            => $input->updateId,
            'firstExecutionRunId' => $input->firstExecutionRunId,
            'updateName'          => $input->updateName,
            'resultType'          => $input->resultType,
            'waitPolicy'          => $input->waitPolicy,
        ];


        return $next($input);
    }

    public function signalWithStart(SignalWithStartInput $input, callable $next): WorkflowExecution
    {
        $this->startedWorkflowWithSignal = [
            'workflowType'      => $input->workflowStartInput->workflowType,
            'signal'            => $input->signalName,
            'workflowId'        => $input->workflowStartInput->workflowId,
            'workflowOptions'   => $input->workflowStartInput->options,
            'workflowHeaders'   => iterator_to_array($input->workflowStartInput->header->getIterator()),
            'workflowArguments' => iterator_to_array($input->workflowStartInput->arguments->getValues()),
            'signalArguments'   => iterator_to_array($input->signalArguments->getValues()),
        ];

        return $next($input);
    }

    public function updateWithStart(UpdateWithStartInput $input, callable $next): UpdateWithStartOutput
    {
        $this->startedWorkflowWithUpdate = [
            'workflowType'      => $input->workflowStartInput->workflowType,
            'update'            => $input->updateInput->updateName,
            'workflowId'        => $input->updateInput->workflowExecution->getID(),
            'workflowRunId'     => $input->updateInput->workflowExecution->getRunID(),
            'workflowOptions'   => $input->workflowStartInput->options,
            'workflowArguments' => iterator_to_array($input->workflowStartInput->arguments->getValues()),
            'workflowHeaders'   => iterator_to_array($input->workflowStartInput->header->getIterator()),
        ];

        return $next($input);
    }

    public function getResult(GetResultInput $input, callable $next): ?ValuesInterface
    {
        $this->getResultWorkflow = [
            'workflowType'  => $input->workflowType,
            'workflowId'    => $input->workflowExecution->getID(),
            'workflowRunId' => $input->workflowExecution->getRunID(),
            'timeout'       => $input->timeout,
            'returnType'    => $input->type,
        ];

        return $next($input);
    }

    public function query(QueryInput $input, callable $next): ?ValuesInterface
    {
        $this->sendQuery = [
            'workflowType'   => $input->workflowType,
            'workflowId'     => $input->workflowExecution->getID(),
            'workflowRunId'  => $input->workflowExecution->getRunID(),
            'query'          => $input->queryType,
            'queryArguments' => iterator_to_array($input->arguments->getValues()),
        ];


        return $next($input);
    }

    public function cancel(CancelInput $input, callable $next): void
    {
        $this->sendCancelWorkflow = [
            'workflowId'    => $input->workflowExecution->getID(),
            'workflowRunId' => $input->workflowExecution->getRunID(),
        ];
    }

    public function terminate(TerminateInput $input, callable $next): void
    {
        $this->sendTerminateWorkflow = [
            'workflowId'    => $input->workflowExecution->getID(),
            'workflowRunId' => $input->workflowExecution->getRunID(),
        ];
    }

    public function describe(DescribeInput $input, callable $next): WorkflowExecutionDescription
    {
        $this->sendDescribe = [
            'workflowId'    => $input->workflowExecution->getID(),
            'workflowRunId' => $input->workflowExecution->getRunID(),
            'namespace'     => $input->namespace,
        ];


        return $next($input);
    }


    /**
     * @return StartedWorkflow|null
     */
    public function getStartedWorkflow(): ?array
    {
        return $this->startedWorkflow;
    }


    /**
     * @return SendSignal|null
     */
    public function getSendSignal(): ?array
    {
        return $this->sendSignal;
    }


    /**
     * @return SendUpdate|null
     */
    public function getSendUpdate(): ?array
    {
        return $this->sendUpdate;
    }


    /**
     * @return StartedWorkflowWithSignal|null
     */
    public function getStartedWorkflowWithSignal(): ?array
    {
        return $this->startedWorkflowWithSignal;
    }


    /**
     * @return StartedWorkflowWithUpdate|null
     */
    public function getStartedWorkflowWithUpdate(): ?array
    {
        return $this->startedWorkflowWithUpdate;
    }


    /**
     * @return GetResultWorkflow|null
     */
    public function getGetResultWorkflow(): ?array
    {
        return $this->getResultWorkflow;
    }


    /**
     * @return SendQuery|null
     */
    public function getSendQuery(): ?array
    {
        return $this->sendQuery;
    }


    /**
     * @return SendCancelWorkflow|null
     */
    public function getSendCancelWorkflow(): ?array
    {
        return $this->sendCancelWorkflow;
    }


    /**
     * @return SendTerminateWorkflow|null
     */
    public function getSendTerminateWorkflow(): ?array
    {
        return $this->sendTerminateWorkflow;
    }


    /**
     * @return SendDescribe|null
     */
    public function getSendDescribe(): ?array
    {
        return $this->sendDescribe;
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
}

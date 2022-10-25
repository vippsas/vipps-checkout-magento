<?php
/**
 * Copyright 2022 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace Vipps\Checkout\Gateway\Command;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface as PaymentCommandManagerInterface;
use Magento\Payment\Gateway;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class CommandManager
 * @package Vipps\Checkout\Model
 */
abstract class CommandManager implements PaymentCommandManagerInterface
{
    /**
     * @var Gateway\Command\CommandManagerInterface
     */
    protected $commandManager;

    /**
     * CommandManager constructor.
     *
     * @param Gateway\Command\CommandManagerInterface $commandManager
     */
    public function __construct(
        Gateway\Command\CommandManagerInterface $commandManager
    ) {
        $this->commandManager = $commandManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param CommandInterface $command
     * @param InfoInterface|null $payment
     * @param array $arguments
     *
     * @return ResultInterface|null
     * @throws CommandException
     */
    public function execute(CommandInterface $command, InfoInterface $payment = null, array $arguments = [])
    {
        return $this->commandManager->execute($command, $payment, $arguments);
    }

    /**
     * @param string $commandCode
     * @param InfoInterface|null $payment
     * @param array $arguments
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     */
    public function executeByCode($commandCode, InfoInterface $payment = null, array $arguments = [])
    {
        return $this->commandManager->executeByCode($commandCode, $payment, $arguments);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $commandCode
     *
     * @return CommandInterface
     * @throws NotFoundException
     */
    public function get($commandCode)
    {
        return $this->commandManager->get($commandCode);
    }
}

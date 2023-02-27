<?php

class ForkController
{
    use traits_class_singleton;

    public function handlerSignal(int $signo, $siginfo): void
    {
        if ($this->imIsFork()) {
            return;
        }

        $sigList = [
            SIGTERM => 'SIGTERM', // окончание процесса. Происходит, когда процесс останавливают командой kill (либо другой командой, посылающей такой сигнал).
            SIGINT => 'SIGINT', // прерывание процесса. Случается, когда пользователь оканчивает выполнение скрипта командой “ctrl+c”.
            // pkill -HUP -f test.php
            SIGHUP => 'SIGHUP', // сигнал перезапуска. Его часто используют для обновления конфигурации работающих процессов без их остановки.
            SIGUSR1 => 'SIGUSR1'
        ];
        $this->out('pcntl_signal : ' . ($sigList[$signo] ?? 'undefined') . ' [' . $signo . ']');

        $sigListFn = [
            SIGHUP => function() {
                $this->out('$sigListFn SIGHUP' . "\n" . 'Reloading config...' . "\n");
            },
            SIGINT => function() {
                $this->out('$sigListFn SIGINT' . "\n");
                exit();
            },
            SIGTERM => function() {
                $this->out('$sigListFn SIGTERM' . "\n");
                exit();
            }
        ];

        if (array_key_exists($signo, $sigListFn)) {
            $sigListFn[$signo]();
        }
    }

    public function handlerSignalEnable(): void
    {
        if ($this->imIsFork()) {
            return;
        }

        // Установка обработчиков сигналов. Подробнее читаем тут https://www.php.net/manual/ru/function.pcntl-signal.php
        foreach ([SIGTERM, SIGHUP, SIGINT, SIGUSR1] as $sig) {
            pcntl_signal($sig, [$this, 'handlerSignal']);
        }
    }

    public function createFork(): ?bool
    {
        if ($this->imIsFork()) {
            return null;
        }
        $fork_pid = pcntl_fork();
        if ($fork_pid == -1) { // ошибка
            $this->_child_fork_error_count += 1;
            return false;
        } elseif ($fork_pid) { // сюда попадет родительский процесс
            $this->_fork_list[] = $fork_pid;
            $this->out('#1 Create child process. $pid:' . $fork_pid);
        } else { // а сюда - дочерний процесс
            $this->_imIsFork();
            $this->out('#2 Hi, I\'m child process.');
        }
        return true;
    }

    public function imIsFork(): bool
    {
        return (bool)$this->_parent_pid;
    }

    public function out($data): void
    {
        echo
            "\n" . '-<<<- ' . $this->_parent_pid . '/' . posix_getpid() . "\n" .
            print_r($data, true) .
            "\n->>>-";
    }

    public function iterFork(): Generator
    {
        if ($this->imIsFork()) {
            return null;
        }

        foreach ($this->_fork_list as $fork_idx => $fork_pid) {
            yield $fork_idx => $fork_pid;
        }
    }

    public function countFork(): ?int
    {
        return $this->imIsFork()
            ? null
            : count($this->_fork_list);
    }

    // Проверяем кто-то завершился или нет. Также нужно проверить завис ли процесс и сколько отожрал ресурсов.
    public function checkForkStatus(): void
    {
        if ($this->imIsFork()) {
            return;
        }

        foreach ($this->_fork_list as $fork_idx => $fork_pid) {
            $status = null;
            $res = pcntl_waitpid($fork_pid, $status, WNOHANG);
//            $this->out([
//                '$childPid' => $fork_pid,
//                '$status' => $status,
//                '$res' => $res
//            ]);
            // If the process has already exited
            if ($res == -1 || $res > 0) {
                unset($this->_fork_list[$fork_idx]);
                $this->out('#3 Unset child process. $fork_pid:' . $fork_pid);
            }
        }
    }

    public function __destruct()
    {
//        if ($this->imIsFork()) {
//            return;
//        }

        $this->out(__METHOD__);
    }

    // private
    private int
        $_master_pid,
        $_parent_pid,
        $_child_fork_error_count = 0;
    private ?array $_fork_list;

    private function _my_construct()
    {
        $this->_master_pid = posix_getpid();
        $this->_parent_pid = 0;
        $this->_fork_list = [];
        $this->out(__METHOD__);
    }

    private function _imIsFork(): void
    {
        $this->_parent_pid = $this->_master_pid;
        $this->_fork_list = null;
    }
}

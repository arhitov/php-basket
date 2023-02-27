<?php

// �����������. ��� ���� ��������� PHP �� ����� ������������� �������.
declare(ticks=1);

const CHILD_PROCESSES_LIMIT = 5;
const CHILD_PROCESSES_START = 2;
const SOME_DELAY = 2;


require __DIR__ . '/Basic.class.php';
require __DIR__ . '/traits_class_singleton.class.php';
require __DIR__ . '/ForkController.class.php';


ForkController::i()->handlerSignalEnable();
ForkController::i()->out('PPID:' . posix_getppid());

$child_necessary_amount = CHILD_PROCESSES_START;
$child_fork_error_count = 0;
$child_list = [];

//$sid = posix_setsid();
//PcntlForkTest::i()->out('posix_setsid: ' . $sid);

while (true) {
    if (ForkController::i()->imIsFork()) {
        break;
    }
    // ��������� ���-�� ���������� ��� ���. ����� ����� ��������� ����� �� ������� � ������� ������� ��������.
    ForkController::i()->checkForkStatus();

    // �������� ������� �����. ��� ����� ���-�� �������� ������.
    if (ForkController::i()->countFork() >= CHILD_PROCESSES_LIMIT) {
        sleep(SOME_DELAY);
        continue;
    }

    // �������� ������ ������������ ����������. ������ ����� ��������.
    if (ForkController::i()->countFork() < $child_necessary_amount) {
        if (ForkController::i()->createFork() && ForkController::i()->imIsFork()) { // � ���� - �������� �������
            // �������� ��������
            for ($i = 0; $i < 5; $i += 1) {
//                PcntlForkTest::i()->out('.');
                sleep(1);
            }
            Application::exit();
            break;
        }
    }

    sleep(SOME_DELAY);
}

ForkController::i()->out('End!');

    // ��������� �������� ������� ��������, ��� ���������� ��� �������� ���������
//    $sid = posix_setsid();
//    if ($sid < 0) {
//        exit;
//    }




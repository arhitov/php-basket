<?php

// Обязательно. Без этой директивы PHP не будет перехватывать сигналы.
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
    // Проверяем кто-то завершился или нет. Также нужно проверить завис ли процесс и сколько отожрал ресурсов.
    ForkController::i()->checkForkStatus();

    // Потомков слишком много. Ждём когда кто-то завершит работу.
    if (ForkController::i()->countFork() >= CHILD_PROCESSES_LIMIT) {
        sleep(SOME_DELAY);
        continue;
    }

    // Потомков меньше необходимого количества. Создаём новых потомков.
    if (ForkController::i()->countFork() < $child_necessary_amount) {
        if (ForkController::i()->createFork() && ForkController::i()->imIsFork()) { // а сюда - дочерний процесс
            // Полезная нагрузка
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

    // Установим дочерний процесс основным, это необходимо для создания процессов
//    $sid = posix_setsid();
//    if ($sid < 0) {
//        exit;
//    }




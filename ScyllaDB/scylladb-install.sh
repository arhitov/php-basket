#!/bin/bash

# Используется как временный каталог.
# ВНИМАНИЕ! После завершения работы удаляется!
TMP_PATH='/tmp/scylladb-driver-installer-temp'
if [ ! -d "$TMP_PATH" ]; then
  mkdir -p "$TMP_PATH"
else
    echo "Folder \"${TMP_PATH}\" exists! Breaking!"
    exit
fi

apt update -y \
    && apt install -y \
        python3 \
        python3-pip \
        unzip \
        mlocate \
        build-essential \
        ninja-build \
        libssl-dev \
        libgmp-dev \
        zlib1g-dev \
        openssl \
        libpcre3-dev \
    && pip3 install cmake \
    && apt-get clean

# Установка LibUV
echo ''
echo 'Installing LibUV'
echo ''
cd $TMP_PATH \
    && rm -rf libuv \
    && git clone --depth 1 -b v1.46.0 https://github.com/libuv/libuv.git libuv \
    && cd libuv \
    && mkdir build \
    && cd build \
    && cmake -DBUILD_TESTING=OFF -DBUILD_BENCHMARKS=OFF -DLIBUV_BUILD_SHARED=ON CMAKE_C_FLAGS="-fPIC" -DCMAKE_BUILD_TYPE="RelWithInfo" -G Ninja .. \
    && ninja install \
    && cd $TMP_PATH \
    && rm -rf libuv

# Установка LibCassandra/LibScyllaDB
echo ''
echo 'Installing ScyllaDB CPP Driver'
echo ''
cd $TMP_PATH \
    && rm -rf scylladb-driver \
    && git clone --depth 1 https://github.com/scylladb/cpp-driver.git scylladb-driver

# Удаляем вывод сообщения "===== Using optimized driver!!! ====="
file_path="${TMP_PATH}/scylladb-driver/src/cluster.cpp"
first_line='static const auto optimized_msg = "===== Using optimized driver!!! =====\\n";'
second_line='std::cout << optimized_msg;'
third_line='LOG_INFO(optimized_msg);'
# Удаляем строки
sed -i "/$first_line/d" "$file_path"
sed -i "/$second_line/d" "$file_path"
sed -i "/$third_line/d" "$file_path"

# Продолжаем установку
cd scylladb-driver \
    && mkdir build \
    && cd build \
    && cmake -DCASS_CPP_STANDARD=17 -DCASS_BUILD_STATIC=ON -DCASS_BUILD_SHARED=ON -DCASS_USE_STD_ATOMIC=ON -DCASS_USE_TIMERFD=ON -DCASS_USE_LIBSSH2=ON -DCASS_USE_ZLIB=ON CMAKE_C_FLAGS="-fPIC" -DCMAKE_CXX_FLAGS="-fPIC -Wno-error=redundant-move" -DCMAKE_BUILD_TYPE="RelWithInfo" -G Ninja .. \
    && ninja install \
    && cd $TMP_PATH \
    && rm -rf scylladb-driver

echo ''
echo 'Installing ScyllaDB PHP Driver'
echo ''
cd $TMP_PATH \
    && rm -rf scylladb-php-driver \
    && git clone --recursive https://github.com/he4rt/scylladb-php-driver.git scylladb-php-driver \
    && cd scylladb-php-driver \
    && cmake --preset Release  \
    && cd out/Release \
    && ninja install \
    && cp ../../cassandra.ini /etc/php/8.2/cli/conf.d/10-cassandra.ini \
    && cp cassandra.so /usr/lib/php/20220829/cassandra.so \
    && rm -rf scylladb-php-driver

php --ini | grep '10-cassandra.ini'

if [ $? -eq 0 ]; then
    echo ''
    echo 'ScyllaDB PHP extension installed successfully'
    echo "Try to run any script that uses the ScyllaDB PHP extension and don't forget to add a valid node IP address"
    echo ''
else
    echo ''
    echo 'ScyllaDB PHP extension installation failed'
    echo ''
fi

rm -r "$TMP_PATH"

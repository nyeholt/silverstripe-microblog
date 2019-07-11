#!/bin/sh

#loads docker env vars into this process
DENV="./docker.env"
if [ -e $DENV ]; then
    . $DENV
fi

#handle env vars
if [ -z "$DOCKER_YARN_PATH" ]; then
    DOCKER_YARN_PATH=""
fi

CMD=$1
CONTAINER="none"
ACTION="pass"
RUN_OPTS="-it"
CONTAINER_PREFIX=$(basename $(pwd))

case "$CMD"
in
    php) 
        CONTAINER="phpcli"
        ACTION="php"
        ;;
    composer)
        CONTAINER="phpcli"
        ACTION="composer"
        ;;
    sspak)
        CONTAINER="phpcli"
        ACTION="sspak"
        ;;
    phing)
        CONTAINER="phpcli"
        ACTION="phing"
        ;;
    cordova) 
        CONTAINER="cordova"
        ACTION="cordova"
        ;;
    simulate)
        CONTAINER="cordova"
        ACTION="simulate"
        ;;
    codecept) 
        CONTAINER="phpcli"
        ACTION="vendor/bin/codecept"
        ;;
    fpm) 
        CONTAINER="php"
        ACTION="bash"
        ;;
    mysql) 
        CONTAINER="mysql"
        ACTION="mysql"
        ;;
    mysqlimport)
        CONTAINER="mysql"
        ACTION="mysql"
        RUN_OPTS="-i"
        ;;
    sel) 
        CONTAINER="selenium"
        ;;
    node) 
        CONTAINER="node"
        ACTION="bash"
        ;;
    yarn) 
        CONTAINER="node"
        ACTION="yarn"
        ;;
esac

case "$2"
in
    cli) 
        ACTION="cli"
        ;;
    exec) 
        ACTION="exec"
        ;;
esac

if [ $CONTAINER = "none" ]; then
    echo "Please provide a container or a command to be run - ./dr.sh {container|command} [arguments] "
    echo "Available containers are as follows; any arguments will be used as docker exec parameters : "
    echo "  php, fpm, mysql, node"
    echo "Available commands are below; any arguments are passed to these commands in the container "
    echo "  composer, phing, codecept, mysqlimport, yarn"

    exit 1;
fi

# check whether we're running in CI on gitlab
if [ ! -z "${CI}" ]; then
    # noop
    RUN_OPTS="-i"
    echo "Executing in CI, setting to no TTY"
fi

CONTAINER_NAME=${CONTAINER_PREFIX}_${CONTAINER}_1

if [ $ACTION = "cli" ]; then
    echo "Dropping to shell in $CONTAINER"
    docker exec ${RUN_OPTS} -u `id -u`:`id -g` ${CONTAINER_NAME} /bin/bash
elif [ $ACTION = "exec" ]; then
    # double shift
    shift
    shift
    echo "Execute bash wrapped command on $CONTAINER"
    docker exec ${RUN_OPTS} -u `id -u`:`id -g` ${CONTAINER_NAME} bash -c "$@"
else
    shift
    echo "Running command $ACTION in $CONTAINER"
    if [ "mysqlimport" = $CMD ]; then
        docker exec ${RUN_OPTS} -u `id -u`:`id -g` ${CONTAINER_NAME} ${ACTION} "$@" < /proc/$$/fd/0
    elif [ "yarn" = $CMD ]; then
        if [ "$DOCKER_YARN_PATH" = "" ]; then
            echo "Please add the DOCKER_YARN_PATH env var"
            exit 1;
        else
            docker exec ${RUN_OPTS} -u `id -u`:`id -g` ${CONTAINER_NAME} bash -c "cd $DOCKER_YARN_PATH; yarn $@"
        fi
    else
        docker exec ${RUN_OPTS} -u `id -u`:`id -g` ${CONTAINER_NAME} ${ACTION} "$@"
    fi
fi

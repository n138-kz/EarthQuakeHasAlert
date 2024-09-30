FROM almalinux:9

USER root
WORKDIR /tmp

RUN set -x && \
    dnf update -y --security

RUN set -x && \
    dnf install -y --allowerasing git

RUN set -x && \
    dnf install -y --allowerasing wget

RUN set -x && \
    dnf install -y --allowerasing make

RUN set -x && \
    dnf install -y --allowerasing gcc

RUN set -x && \
    dnf install -y --allowerasing curl

RUN set -x && \
    dnf install -y --allowerasing jq

RUN set -x && \
    dnf install -y --allowerasing file

ENTRYPOINT [ "sh" ]
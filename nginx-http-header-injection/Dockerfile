FROM nginx

RUN apt-get update

# Python3とpip3のインストール
RUN apt-get install -y openssl libssl-dev python3 python3-pip \
    && pip3 install supervisor --break-system-packages \
    && mkdir -p /var/log/supervisor \
    && ln -s /etc/supervisor/supervisord.conf /etc/supervisord.conf

RUN apt-get install -y incron

ADD supervisor /etc/supervisor
ADD incron.d /etc/incron.d

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]


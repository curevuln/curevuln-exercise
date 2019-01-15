FROM nginx

RUN apt-get update

RUN apt-get install -y openssl libssl-dev python python-pip \
    && pip install supervisor \
    && mkdir -p /var/log/supervisor \
    && ln -s /etc/supervisor/supervisord.conf /etc/supervisord.conf

RUN apt-get install -y incron

ADD supervisor /etc/supervisor
ADD incron.d /etc/incron.d

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

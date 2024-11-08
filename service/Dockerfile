FROM ruby:3.3.5

ENV APP_HOME /app
WORKDIR $APP_HOME

COPY Gemfile* $APP_HOME/
RUN bundle install

COPY . $APP_HOME

EXPOSE 4567

CMD ["ruby", "app.rb", "-o", "0.0.0.0"]

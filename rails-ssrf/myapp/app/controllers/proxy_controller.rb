class ProxyController < ApplicationController
  def index
    if params[:url].present?
      if Myapp::UrlBlocker.blocked_url?(params[:url])
        @message = "許可さていないURLです"
      else
        response = Faraday.get params[:url]
        @message = response.body
      end
    end
  end
end

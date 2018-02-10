require 'resolv'
require 'addressable'

module Myapp
  class UrlBlocker
    class << self

      def blocked_url?(url)
        return false if url.nil?

        blocked_ips = ["127.0.0.1", "::1", "0.0.0.0"]
        blocked_ips.concat(Socket.ip_address_list.map(&:ip_address))

        begin
          uri = Addressable::URI.parse(url)
          server_ips = Resolv.getaddresses(uri.hostname)
          return true if (blocked_ips & server_ips).any?
        rescue Addressable::URI::InvalidURIError
          return true
        rescue SocketError
          return false
        end
        
        false
      end
    end
  end
end

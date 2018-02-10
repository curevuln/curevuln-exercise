require 'test_helper'

class ProxyControllerTest < ActionDispatch::IntegrationTest
  test "should get index" do
    get proxy_index_url
    assert_response :success
  end

end

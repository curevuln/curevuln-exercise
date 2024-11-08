require 'sinatra'
require 'kramdown'
require 'fileutils'

set :server, 'puma'
set :public_folder, 'static'

CHALLENGES = {
  'content-security-policy'               =>'http://localhost:8000',
  'nginx-http-header-injection'           =>'http://localhost:8001',
  'php-access'                            =>'http://localhost:8002',
  'php-csrf'                              =>'http://localhost:8003',
  'php-docroot'                           =>'http://localhost:8004',
  'php-dom-based-xss'                     =>'http://localhost:8005',
  'php-markdown-xss'                      =>'http://localhost:8006',
  'php-os-command-injection'              =>'http://localhost:8007',
  'php-reflected-xss'                     =>'http://localhost:8008',
  'php-reflected-xss-form'                =>'http://localhost:8009',
  'php-sessionFixation'                   =>'http://localhost:8010',
  'php-sql-injection'                     =>'http://localhost:8011',
  'php-ssrf'                              =>'http://localhost:8012',
  'php-stored-xss'                        =>'http://localhost:8013',
  'php-upload-file-rce'                   =>'http://localhost:8014',
  'rails-javascript-scheme-xss'           =>'http://localhost:8015',
  'rails-ssrf'                            =>'http://localhost:8016',
  'server-side-template-injection-smarty' =>'http://localhost:8017',
  'vuejs-template-injection-on-php'       =>'http://localhost:8018'
}

TEXTS = CHALLENGES.keys.map {|name| 
  [name, Dir.glob("/challenges/#{name}/text/*")]
}.to_h

get '/' do
  @challenges = CHALLENGES
  @texts = TEXTS.map{|name, path| {name => File.basename(path)}}
  erb :list
end

get '/:name' do
  name = params[:name]
  if !CHALLENGES.keys.include? name
    status 404
    '404 - Challenge not found'
  else
    @texts = TEXTS[name]
    @title = name
    @markdown_contents = @texts.map { |file_path|
      markdown_content = File.read(file_path)
      html_content = Kramdown::Document.new(markdown_content).to_html
      { file_path: file_path, html_content: html_content }
    }
    erb :challenge
  end
end

Rails.application.routes.draw do
  root :to => 'users#index'
  get 'users/index'

end

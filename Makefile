.PHONY: build up down down-all up-all health
	
DIRS := content-security-policy nginx-http-header-injection php-access php-csrf php-docroot php-dom-based-xss php-markdown-xss php-os-command-injection php-reflected-xss php-reflected-xss-form php-sessionFixation php-sql-injection php-ssrf php-stored-xss php-upload-file-rce rails-javascript-scheme-xss rails-ssrf server-side-template-injection-smarty vuejs-template-injection-on-php service
COMPOSE_FILES := $(foreach dir,$(DIRS),$(dir)/compose.yaml)


build:
	@for dir in $(DIRS); do \
		echo "Building container for $$dir"; \
		if [ -d $$dir ]; then \
			cd $$dir; \
			docker compose build; \
			cd -; \
		fi \
	done

up:
	@if command -v fzf >/dev/null; then \
			selected_target=`echo $(DIRS) | tr ' ' '\n' | fzf`; \
	else \
			echo "fzf not found. Please enter the target manually:"; \
			echo "Available targets: " $(DIRS); \
			read -p "Enter target: " selected_target; \
	fi; \
	if [ -n "$$selected_target" ]; then \
			echo "Starting $$selected_target service..."; \
			cd $$selected_target; \
			docker compose up -d ;\
	else \
			echo "No target selected"; \
	fi

down:
	@if command -v fzf >/dev/null; then \
			selected_target=`echo $(DIRS) | tr ' ' '\n' | fzf`; \
	else \
			echo "fzf not found. Please enter the target manually:"; \
			echo "Available targets: " $(DIRS); \
			read -p "Enter target: " selected_target; \
	fi; \
	if [ -n "$$selected_target" ]; then \
			echo "Stopping $$selected_target service..."; \
			cd $$selected_target; \
			docker compose down ;\
	else \
			echo "No target selected"; \
	fi

down-all:
	@$(foreach file,$(COMPOSE_FILES), \
			docker compose -f $(file) down && echo "Stopped $(file)";)

up-all:
	@$(foreach file,$(COMPOSE_FILES), \
			docker compose -f $(file) up -d && echo "Started $(file)";)
	

health:
	@for port in $$(seq 8000 8018); do \
		echo "Testing $$port..."; \
		curl -sSf -o /dev/null http://localhost:$$port/ || echo "Port $$port failed"; \
	done

.PHONY: help
help: #! Show this help message
	@echo 'Usage: make [target] ... '
	@echo ''
	@echo 'Targets:'
	@grep -h -F '#!' $(MAKEFILE_LIST) | grep -v grep | sed 's/:.*#!/:/' | column -t -s":"

.PHONY: check
check: #! Run all quality control checks
check: lint test

.PHONY: lint
lint: #! Run formatters, linters, and other quality control tools
	@composer cs-check
	@composer phpstan

.PHONY: test
test: #! Run all automated tests
	@composer phpunit

.PHONY: all clean test

PLUGINS_DIR := .
BUILD_DIR := ../backend/storage/app/plugins
SONAME := libplugins.so
HEADER := libplugins.h

all:
	@echo "Building Go plugins as C shared library..."
	@mkdir -p $(BUILD_DIR)
	cd $(PLUGINS_DIR) && CGO_ENABLED=1 go build -buildmode=c-shared -o $(BUILD_DIR)/$(SONAME) -ldflags="-s -w" .
	@echo "Built: $(BUILD_DIR)/$(SONAME)"
	@echo "Header: $(BUILD_DIR)/$(HEADER)"

clean:
	rm -f $(BUILD_DIR)/$(SONAME) $(BUILD_DIR)/$(HEADER)

test:
	cd $(PLUGINS_DIR) && go test ./...

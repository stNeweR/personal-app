package main

/*
#include <stdlib.h>
*/
import "C"
import (
	"encoding/json"
	"unsafe"

	"personal-app/plugins/shared"

	// Register all plugins via side-effect imports
	_ "personal-app/plugins/mail_notifier"
	_ "personal-app/plugins/playlist"
	_ "personal-app/plugins/telegram"
	_ "personal-app/plugins/yandex_calendar"
)

// plugin_execute runs a plugin action and returns a JSON string.
// The returned pointer must be freed by the caller using plugin_free.
//
//export plugin_execute
func plugin_execute(pluginName *C.char, action *C.char, jsonInput *C.char) *C.char {
	name := C.GoString(pluginName)
	act := C.GoString(action)
	input := json.RawMessage(C.GoString(jsonInput))

	result, err := shared.Execute(name, act, input)

	response := map[string]any{"success": err == nil}
	if err != nil {
		response["error"] = err.Error()
	} else {
		response["data"] = result
	}

	bytes, _ := json.Marshal(response)
	return C.CString(string(bytes))
}

// plugin_free releases memory allocated by plugin_execute.
//
//export plugin_free
func plugin_free(ptr *C.char) {
	C.free(unsafe.Pointer(ptr))
}

func main() {}

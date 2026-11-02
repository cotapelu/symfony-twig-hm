package com.app.modules.appcore

import kotlin.reflect.KCallable
import kotlin.reflect.KClass

class ModuleFacade(
    private val moduleRegistry: ModuleRegistry
) {
    fun get(moduleName: String): Any? {
        return moduleRegistry.getModule(moduleName)
    }

    fun call(moduleName: String, methodName: String, vararg args: Any?): Any? {
        val module = get(moduleName)
            ?: throw IllegalArgumentException("Module $moduleName not loaded")

        val kClass = module::class
        val method = findMethod(kClass, methodName)
            ?: throw NoSuchMethodException("Method $methodName not found in module $moduleName")

        return method.call(module, *args)
    }

    fun has(moduleName: String): Boolean {
        return moduleRegistry.isLoaded(moduleName)
    }

    fun getAll(): Map<String, Any> {
        return moduleRegistry.getLoadedModules()
    }

    fun getDependencyGraph(): Map<String, List<String>> {
        val graph = mutableMapOf<String, List<String>>()
        val modules = getAll()

        modules.forEach { (name, _) ->
            graph[name] = moduleRegistry.getDependencies(name)
        }

        return graph
    }

    fun canCall(moduleName: String, methodName: String): Boolean {
        if (!has(moduleName)) {
            return false
        }

        val module = get(moduleName) ?: return false
        val kClass = module::class

        return findMethod(kClass, methodName) != null
    }

    private fun findMethod(kClass: KClass<*>, methodName: String): KCallable<*>? {
        return kClass.members.find { it.name == methodName }
    }
}
package com.app.modules.appcore

import kotlin.reflect.KClass
import kotlin.reflect.full.primaryConstructor

data class ModuleInfo(
    val kClass: KClass<*>,
    val path: String,
    val name: String
)

class ModuleRegistry {
    private val modules = mutableMapOf<String, ModuleInfo>()
    private val loadedModules = mutableMapOf<String, Any>()
    private val dependencies = mutableMapOf<String, List<String>>()
    private val loadOrder = mutableListOf<String>()

    fun discover(name: String, path: String) {
        try {
            // In Kotlin, we would typically use classpath scanning or configuration
            // For simplicity, we'll assume modules are registered manually
            // This is a placeholder implementation
            println("Discovering module: $name at $path")
        } catch (e: Exception) {
            throw RuntimeException("Failed to discover module $name", e)
        }
    }

    fun register(name: String, kClass: KClass<*>, path: String = "") {
        modules[name] = ModuleInfo(kClass, path, name)
    }

    fun load(moduleName: String) {
        if (loadedModules.containsKey(moduleName)) {
            return
        }

        if (!modules.containsKey(moduleName)) {
            throw IllegalArgumentException("Module $moduleName not found")
        }

        val moduleInfo = modules[moduleName]!!

        try {
            val module = moduleInfo.kClass.primaryConstructor?.call()
                ?: throw RuntimeException("Cannot instantiate module $moduleName")

            loadedModules[moduleName] = module
            dependencies[moduleName] = (module as? Module)?.getDependencies() ?: emptyList()

        } catch (e: Exception) {
            throw RuntimeException("Failed to load module $moduleName", e)
        }
    }

    fun getLoadedModules(): Map<String, Any> = loadedModules

    fun getModule(name: String): Any? = loadedModules[name]

    fun isLoaded(name: String): Boolean = loadedModules.containsKey(name)

    fun getDependencies(moduleName: String): List<String> = dependencies[moduleName] ?: emptyList()

    fun getLoadOrder(): List<String> {
        if (loadOrder.isEmpty()) {
            calculateLoadOrder()
        }
        return loadOrder
    }

    private fun calculateLoadOrder() {
        val visited = mutableSetOf<String>()
        val visiting = mutableSetOf<String>()
        val order = mutableListOf<String>()

        loadedModules.keys.forEach { module ->
            visitModule(module, visited, visiting, order)
        }

        loadOrder.clear()
        loadOrder.addAll(order)
    }

    private fun visitModule(
        module: String,
        visited: MutableSet<String>,
        visiting: MutableSet<String>,
        order: MutableList<String>
    ) {
        if (visited.contains(module)) {
            return
        }

        if (visiting.contains(module)) {
            throw RuntimeException("Circular dependency detected involving module $module")
        }

        visiting.add(module)

        val deps = getDependencies(module)
        deps.forEach { dependency ->
            if (loadedModules.containsKey(dependency)) {
                visitModule(dependency, visited, visiting, order)
            }
        }

        visiting.remove(module)
        visited.add(module)
        order.add(module)
    }
}

interface Module {
    fun getDependencies(): List<String> = emptyList()
}
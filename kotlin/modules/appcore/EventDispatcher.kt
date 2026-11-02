package com.app.modules.appcore

import kotlinx.coroutines.*
import kotlin.coroutines.CoroutineContext

data class EventListener(
    val listener: (Event) -> Unit,
    val priority: Int = 0
)

interface Event {
    val name: String
    val data: Map<String, Any>
}

abstract class BaseEvent(
    override val name: String,
    override val data: Map<String, Any> = emptyMap()
) : Event

class EventDispatcher(
    private val asyncDispatch: Boolean = false,
    private val maxRetries: Int = 3,
    private val coroutineContext: CoroutineContext = Dispatchers.Default
) {
    private val listeners = mutableMapOf<String, MutableList<EventListener>>()
    private val wildcardListeners = mutableMapOf<String, EventListener>()

    fun addListener(eventName: String, listener: (Event) -> Unit, priority: Int = 0): String {
        val listenerId = "listener_${System.nanoTime()}"

        if (eventName == "*") {
            wildcardListeners[listenerId] = EventListener(listener, priority)
        } else {
            listeners.getOrPut(eventName) { mutableListOf() }
                .add(EventListener(listener, priority))
            sortListeners(eventName)
        }

        return listenerId
    }

    fun removeListener(eventName: String, listenerId: String): Boolean {
        if (eventName == "*") {
            return wildcardListeners.remove(listenerId) != null
        }

        return listeners[eventName]?.removeIf { it == wildcardListeners[listenerId] } ?: false
    }

    fun dispatch(event: Event) {
        try {
            dispatchToListeners(event.name, event)
            dispatchToListeners("*", event)
        } catch (e: Exception) {
            throw RuntimeException("One or more listeners failed: ${e.message}", e)
        }
    }

    suspend fun dispatchAsync(event: Event) = withContext(coroutineContext) {
        if (!asyncDispatch) {
            dispatch(event)
            return@withContext
        }

        coroutineScope {
            launch { dispatch(event) }
        }
    }

    fun getListeners(eventName: String): List<EventListener> = listeners[eventName] ?: emptyList()

    fun getWildcardListeners(): Map<String, EventListener> = wildcardListeners

    fun hasListeners(eventName: String): Boolean =
        listeners.containsKey(eventName) || wildcardListeners.isNotEmpty()

    fun clearListeners(eventName: String? = null) {
        if (eventName == null) {
            listeners.clear()
            wildcardListeners.clear()
        } else {
            listeners.remove(eventName)
        }
    }

    private fun dispatchToListeners(eventName: String, event: Event) {
        val eventListeners = listeners[eventName] ?: emptyList()

        eventListeners.forEach { listener ->
            invokeListener(listener, event)
        }
    }

    private fun invokeListener(listener: EventListener, event: Event) {
        var retries = 0

        while (retries < maxRetries) {
            try {
                listener.listener(event)
                return
            } catch (e: Exception) {
                retries++
                if (retries >= maxRetries) {
                    throw e
                }
                Thread.sleep(100L * retries)
            }
        }
    }

    private fun sortListeners(eventName: String) {
        listeners[eventName]?.sortByDescending { it.priority }
    }
}
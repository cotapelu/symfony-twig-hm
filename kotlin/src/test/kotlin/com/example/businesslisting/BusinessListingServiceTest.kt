package com.example.businesslisting

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.Instant

class BusinessListingServiceTest {

    private val service = BusinessListingService()

    @Test
    fun `createListing should return listing with generated id`() {
        val listing = service.createListing(
            name = "Test Business",
            category = "Food",
            address = "123 Main St",
            ownerId = "user1"
        )
        assertNotNull(listing.id)
        assertEquals("Test Business", listing.name)
        assertEquals("Food", listing.category)
        assertEquals("user1", listing.ownerId)
    }

    @Test
    fun `createListing should require name`() {
        val ex = assertThrows<IllegalArgumentException> {
            service.createListing("", "cat", "addr", "user1")
        }
        assertEquals("Name is required", ex.message)
    }

    @Test
    fun `getListing should return listing by id`() {
        val created = service.createListing("Biz", "cat", "addr", "user1")
        val retrieved = service.getListing(created.id!!)
        assertNotNull(retrieved)
        assertEquals("Biz", retrieved!!.name)
    }

    @Test
    fun `getListingByOwner should return all listings for owner`() {
        service.createListing("Biz1", "cat", "addr", "user1")
        service.createListing("Biz2", "cat", "addr", "user1")
        service.createListing("Biz3", "cat", "addr", "user2")

        val user1Listings = service.getListingByOwner("user1")
        assertEquals(2, user1Listings.size)
    }

    @Test
    fun `searchListings should filter by category and name`() {
        service.createListing("Pizza Place", "Food", "addr", "user1")
        service.createListing("Burger Joint", "Food", "addr", "user1")
        service.createListing("Yoga Studio", "Health", "addr", "user1")

        var foodBizs = service.searchListings(category = "Food")
        assertEquals(2, foodBizs.size)

        var pizza = service.searchListings(nameContains = "Pizza")
        assertEquals(1, pizza.size)
        assertEquals("Pizza Place", pizza[0].name)
    }

    @Test
    fun `updateStatus should change listing status`() {
        val listing = service.createListing("Test", "cat", "addr", "user1")
        val updated = service.updateStatus(listing.id!!, "INACTIVE")
        assertNotNull(updated)
        assertEquals("INACTIVE", updated!!.status)
    }

    @Test
    fun `deleteListing should remove listing`() {
        val listing = service.createListing("ToDelete", "cat", "addr", "user1")
        val result = service.deleteListing(listing.id!!)
        assertTrue(result)
        assertNull(service.getListing(listing.id!!))
    }
}

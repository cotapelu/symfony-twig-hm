package app

import io.ktor.server.application.*
import io.ktor.server.auth.*
import io.ktor.server.auth.jwt.*
import com.auth0.jwt.interfaces.JWTPrincipal
import io.ktor.server.response.*
import io.ktor.server.request.*
import io.ktor.http.*
import com.qcoder.kotlin.observability.CorrelationId
import com.qcoder.kotlin.observability.Metrics
import com.qcoder.kotlin.observability.installObservabilityEndpoints

import peertopeer.FileSharingService
import peertopeer.configurePeerToPeerRouting
import credentialmanager.PasswordManagerService
import credentialmanager.configureCredentialManagerRouting
import identitymanagement.IdentitymanagementService
import identitymanagement.IdentitymanagementRepository
import identitymanagement.configureIdentitymanagementRoutes
import identitymanagement.IdentitymanagementHandler
import hotelbooking.HotelBookingService
import hotelbooking.HotelRepositoryImpl
import hotelbooking.RoomTypeRepositoryImpl
import hotelbooking.RoomRepositoryImpl
import hotelbooking.BookingRepositoryImpl
import hotelbooking.ReviewRepositoryImpl
import hotelbooking.HotelBookingHandler
import hotelbooking.configureHotelBookingRoutes
import flighttickets.FlightBookingService
import flighttickets.FlightRepositoryImpl
import flighttickets.SeatRepositoryImpl
import flighttickets.FlightBookingRepositoryImpl
import flighttickets.AirlineRepositoryImpl
import flighttickets.AirportRepositoryImpl
import flighttickets.FlightBookingHandler
import flighttickets.configureFlightBookingRoutes
import carrental.CarRentalService
import carrental.CarRepositoryImpl
import carrental.CarBookingRepositoryImpl
import carrental.CarReviewRepositoryImpl
import carrental.CarRentalHandler
import carrental.configureCarRentalRoutes
import restaurantbooking.RestaurantBookingService
import restaurantbooking.RestaurantRepositoryImpl
import restaurantbooking.TableRepositoryImpl
import restaurantbooking.ReservationRepositoryImpl
import restaurantbooking.ReviewRepositoryImpl
import restaurantbooking.RestaurantBookingHandler
import restaurantbooking.configureRestaurantBookingRoutes
import travelpackages.TravelPackageRepository
import travelpackages.TravelPackageService
import travelpackages.TravelPackageHandler
import travelpackages.configureTravelPackageRoutes
import eventsbooking.EventRepository
import eventsbooking.VenueRepository
import eventsbooking.EventBookingRepository
import eventsbooking.EventReviewRepository
import eventsbooking.EventsBookingService
import eventsbooking.eventsBookingRoutes
import experiences.ExperienceRepository
import experiences.ExperienceBookingRepository
import experiences.ExperienceReviewRepository
import experiences.ExperiencesService
import experiences.experiencesRoutes
import payment.PaymentHandler
import payment.IdempotencyService
import payment.PaymentRepository
import payment.PaymentService
import payment.configurePaymentRoutes

val serverHost = System.getenv("SERVER_HOST") ?: "0.0.0.0"
val serverPort = System.getenv("SERVER_PORT")?.toIntOrNull() ?: 9030

fun main() {
    embeddedServer(Netty, port = serverPort, host = serverHost, module = Application::module)
        .start(wait = true)
}

fun Application.module() {
    val storagePath = System.getenv("STORAGE_PATH") ?: "./data"
    val masterKey = System.getenv("MASTER_KEY") ?: "default-master-key"
    val jwtSecret = System.getenv("JWT_SECRET") ?: "default-jwt-secret"

    val peerToPeerService = FileSharingService(storagePath)
    val credentialManagerService = PasswordManagerService(masterKey)

    val identityRepository = IdentitymanagementRepository()
    val identityService = IdentitymanagementService(identityRepository)
    val identityHandler = IdentitymanagementHandler(identityService, jwtSecret)

    val hotelRepository = HotelRepositoryImpl()
    val roomTypeRepository = RoomTypeRepositoryImpl()
    val roomRepository = RoomRepositoryImpl()
    val bookingRepository = BookingRepositoryImpl()
    val reviewRepository = ReviewRepositoryImpl()
    hotelRepository.seedSampleData()
    roomTypeRepository.seedSampleData()
    val hotelBookingService = HotelBookingService(
        hotelRepository, roomTypeRepository, roomRepository, bookingRepository, reviewRepository
    )
    val hotelBookingHandler = HotelBookingHandler(hotelBookingService)

    val flightRepository = FlightRepositoryImpl()
    val seatRepository = SeatRepositoryImpl()
    val flightBookingRepository = FlightBookingRepositoryImpl()
    val airlineRepository = AirlineRepositoryImpl()
    val airportRepository = AirportRepositoryImpl()
    airlineRepository.seedSampleData()
    airportRepository.seedSampleData()
    val flightBookingService = FlightBookingService(
        flightRepository, seatRepository, flightBookingRepository, airlineRepository, airportRepository
    )
    val flightBookingHandler = FlightBookingHandler(flightBookingService)

    val carRepository = CarRepositoryImpl()
    val carBookingRepository = CarBookingRepositoryImpl()
    val carReviewRepository = CarReviewRepositoryImpl()
    carRepository.seedSampleData()
    val carRentalService = CarRentalService(carRepository, carBookingRepository, carReviewRepository)
    val carRentalHandler = CarRentalHandler(carRentalService)

    val restaurantRepository = RestaurantRepositoryImpl()
    val tableRepository = TableRepositoryImpl()
    val reservationRepository = ReservationRepositoryImpl()
    val restaurantReviewRepository = restaurantbooking.ReviewRepositoryImpl()
    restaurantRepository.seedSampleData()
    tableRepository.seedSampleData()
    val restaurantBookingService = RestaurantBookingService(
        restaurantRepository, tableRepository, reservationRepository, restaurantReviewRepository
    )
    val restaurantBookingHandler = RestaurantBookingHandler(restaurantBookingService)

    val travelPackageRepository = TravelPackageRepository()
    travelPackageRepository.seedSampleData()
    val travelPackageService = TravelPackageService(travelPackageRepository)
    val travelPackageHandler = TravelPackageHandler(travelPackageService)

    val eventRepository = EventRepository()
    val venueRepository = VenueRepository()
    val eventBookingRepository = EventBookingRepository()
    val eventReviewRepository = EventReviewRepository()
    eventRepository.seedSampleData()
    venueRepository.seedSampleData()
    val eventsBookingService = EventsBookingService(
        eventRepository, venueRepository, eventBookingRepository, eventReviewRepository
    )

    val experienceRepository = ExperienceRepository()
    val experienceBookingRepository = ExperienceBookingRepository()
    val experienceReviewRepository = ExperienceReviewRepository()
    experienceRepository.seedSampleData()
    val experiencesService = ExperiencesService(
        experienceRepository, experienceBookingRepository, experienceReviewRepository
    )

    // Payment module
    val paymentRepository = PaymentRepository()
    val paymentService = PaymentService(paymentRepository)
    val idempotencyService = IdempotencyService()
    val paymentHandler = PaymentHandler(paymentService, idempotencyService)

    // Observability: Metrics interceptor with correlation ID
    intercept(io.ktor.server.application.ApplicationCallPipeline.Call) {
        val correlationId = CorrelationId.getOrGenerate(call)
        val startTime = System.currentTimeMillis()
        try {
            proceed()
        } finally {
            val duration = System.currentTimeMillis() - startTime
            val status = call.response.status()
            Metrics.record(status?.value ?: 200, duration)
        }
    }

    // Health & metrics endpoints
    installObservabilityEndpoints()

    install(ContentNegotiation) {
        json()
    }

    install(Authentication) {
        jwt("auth-jwt") {
            realm = "Community Platform"
            verifier(
                com.auth0.jwt.JWT.require(com.auth0.jwt.algorithms.Algorithm.HMAC256(jwtSecret))
                    .withIssuer("kotlin-backend")
                    .build()
            )
            validate { credential ->
                if (credential.payload.subject != null) {
                    com.auth0.jwt.interfaces.JWTPrincipal(credential.payload)
                } else {
                    null
                }
            }
        }
    }

    install(CORS) {
        anyHost()
        allowHeader(HttpHeaders.ContentType)
        allowHeader(HttpHeaders.Authorization)
        allowMethod(HttpMethod.Get)
        allowMethod(HttpMethod.Post)
        allowMethod(HttpMethod.Put)
        allowMethod(HttpMethod.Delete)
    }

    configureRouting(
        peerToPeerService,
        credentialManagerService,
        identityHandler,
        hotelBookingHandler,
        flightBookingHandler,
        carRentalHandler,
        restaurantBookingHandler,
        travelPackageHandler,
        eventsBookingService,
        experiencesService,
        paymentHandler
    )
}

// Extension to safely get authenticated user ID from JWT
fun ApplicationCall.requireUserId(): String {
    val principal = call.principal<JWTPrincipal>()
        ?: throw IllegalArgumentException("Authentication required")
    return principal.subject ?: throw IllegalArgumentException("Invalid token: missing subject")
}

fun Application.configureRouting(
    peerToPeerService: FileSharingService,
    credentialManagerService: PasswordManagerService,
    identityHandler: IdentitymanagementHandler,
    hotelBookingHandler: HotelBookingHandler,
    flightBookingHandler: FlightBookingHandler,
    carRentalHandler: CarRentalHandler,
    restaurantBookingHandler: RestaurantBookingHandler,
    travelPackageHandler: TravelPackageHandler,
    eventsBookingService: EventsBookingService,
    experiencesService: ExperiencesService,
    paymentHandler: PaymentHandler
) {
    routing {
        get("/") {
            call.respondText("Kotlin Modular Monolith - Community Platform")
        }
        
        // Health checks are provided by ObservabilityMiddleware installHealthChecks()
        // Additional module-specific routes

        // All API routes require JWT authentication
        authenticate("auth-jwt") {
            configurePeerToPeerRouting(peerToPeerService)
            configureCredentialManagerRouting(credentialManagerService)
            configureIdentitymanagementRoutes(identityHandler)
            configureHotelBookingRoutes(hotelBookingHandler)
            configureFlightBookingRoutes(flightBookingHandler)
            configureCarRentalRoutes(carRentalHandler)
            configureRestaurantBookingRoutes(restaurantBookingHandler)
            configureTravelPackageRoutes(travelPackageHandler)
            eventsBookingRoutes()
            experiencesRoutes()
            configurePaymentRoutes(paymentHandler)
        }
    }
}

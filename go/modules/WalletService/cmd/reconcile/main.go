//go:build ignore

package main

import (
	"context"
	"database/sql"
	"fmt"
	"log"
	"os"

	_ "github.com/lib/pq"
	"github.com/spf13/cobra"
	"gorm.io/driver/postgres"
	"gorm.io/gorm"

	"community.local/modules/WalletService/internal/models"
	"community.local/modules/WalletService/repository"
)

var (
	dbHost     string
	dbPort     string
	dbUser     string
	dbPassword string
	dbName     string
)

func init() {
	rootCmd.PersistentFlags().StringVar(&dbHost, "host", "localhost", "PostgreSQL host")
	rootCmd.PersistentFlags().StringVar(&dbPort, "port", "5432", "PostgreSQL port")
	rootCmd.PersistentFlags().StringVar(&dbUser, "user", "", "PostgreSQL user (required)")
	rootCmd.PersistentFlags().StringVar(&dbPassword, "password", "", "PostgreSQL password (required)")
	rootCmd.PersistentFlags().StringVar(&dbName, "dbname", "", "PostgreSQL database name (required)")
}

var rootCmd = &cobra.Command{
	Use:   "reconcile",
	Short: "Reconcile financial balances",
}

var walletsCmd = &cobra.Command{
	Use:   "wallets",
	Short: "Reconcile wallet balances",
	Run: func(cmd *cobra.Command, args []string) {
		if dbUser == "" || dbPassword == "" || dbName == "" {
			fmt.Println("--user, --password, and --dbname are required")
			os.Exit(1)
		}

		dsn := fmt.Sprintf("host=%s port=%s user=%s password=%s dbname=%s sslmode=disable",
			dbHost, dbPort, dbUser, dbPassword, dbName)
		db, err := gorm.Open(postgres.Open(dsn), &gorm.Config{})
		if err != nil {
			log.Fatalf("failed to connect: %v", err)
		}

		walletRepo := repository.NewWalletRepository(db)
		txRepo := repository.NewTransactionRepository(db)

		// Process all wallets
		var wallets []models.Wallet
		if err := db.Find(&wallets).Error; err != nil {
			log.Fatalf("failed to list wallets: %v", err)
		}

		fmt.Printf("Reconciling %d wallets...\n", len(wallets))
		mismatches := 0
		fixed := 0

		for _, wallet := range wallets {
			sum, err := txRepo.SumCompletedByWalletID(wallet.ID)
			if err != nil {
				log.Printf("error summing transactions for wallet %s: %v", wallet.ID, err)
				continue
			}

			if wallet.Balance != sum {
				mismatches++
				fmt.Printf("Mismatch: Wallet ID %s, Owner %s, Balance %d, Sum %d\n", wallet.ID, wallet.OwnerID, wallet.Balance, sum)
				// Auto-fix
				wallet.Balance = sum
				if err := db.Save(&wallet).Error; err != nil {
					log.Printf("failed to fix wallet %s: %v", wallet.ID, err)
				} else {
					fixed++
				}
			}
		}

		fmt.Printf("Total: %d wallets, mismatches: %d, fixed: %d\n", len(wallets), mismatches, fixed)
	},
}

var accountsCmd = &cobra.Command{
	Use:   "accounts",
	Short: "Reconcile banking account balances",
	Run: func(cmd *cobra.Command, args []string) {
		if dbUser == "" || dbPassword == "" || dbName == "" {
			fmt.Println("--user, --password, and --dbname are required")
			os.Exit(1)
		}

		dsn := fmt.Sprintf("host=%s port=%s user=%s password=%s dbname=%s sslmode=disable",
			dbHost, dbPort, dbUser, dbPassword, dbName)
		db, err := gorm.Open(postgres.Open(dsn), &gorm.Config{})
		if err != nil {
			log.Fatalf("failed to connect: %v", err)
		}

		// Assuming Banking account model is Account with fields ID, Balance
		type Account struct {
			ID     string `gorm:"primaryKey"`
			UserID string `gorm:"column:user_id"`
			Balance int64 `gorm:"column:balance"`
		}
		table := "go.banking_account"

		var accounts []Account
		if err := db.Raw(fmt.Sprintf("SELECT id, user_id, balance FROM %s", table)).Scan(&accounts).Error; err != nil {
			log.Fatalf("failed to list accounts: %v", err)
		}

		fmt.Printf("Reconciling %d banking accounts...\n", len(accounts))
		mismatches := 0
		fixed := 0

		for _, acc := range accounts {
			var sum struct {
				Sum int64
			}
			// Sum completed transactions where account_id = acc.ID. Transactions table name might be banking_transaction.
			if err := db.Raw(fmt.Sprintf("SELECT SUM(amount) as sum FROM go.banking_transaction WHERE account_id = ? AND status = ?", acc.ID, "completed")).Scan(&sum).Error; err != nil {
				log.Printf("error summing transactions for account %s: %v", acc.ID, err)
				continue
			}

			if acc.Balance != sum.Sum {
				mismatches++
				fmt.Printf("Mismatch: Account ID %s, User %s, Balance %d, Sum %d\n", acc.ID, acc.UserID, acc.Balance, sum.Sum)
				// Auto-fix
				if err := db.Exec(fmt.Sprintf("UPDATE go.banking_account SET balance = ? WHERE id = ?", sum.Sum, acc.ID)).Error; err != nil {
					log.Printf("failed to fix account %s: %v", acc.ID, err)
				} else {
					fixed++
				}
			}
		}

		fmt.Printf("Total: %d accounts, mismatches: %d, fixed: %d\n", len(accounts), mismatches, fixed)
	},
}

func main() {
	rootCmd.AddCommand(walletsCmd)
	rootCmd.AddCommand(accountsCmd)
	if err := rootCmd.Execute(); err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
}
